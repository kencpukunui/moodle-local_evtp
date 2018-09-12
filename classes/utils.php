<?php
/**
 * EVTP Training Plans
 *
 * Utility functions.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evtp;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility functions.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * @const item status header
     */
    const ITEM_HEADER = 1;
    
    /**
     * @const item status mandatory
     */
    const ITEM_MANDATORY = 2;

    /**
     * @const item status elective
     */
    const ITEM_ELECTIVE = 3;

    /**
     * @const registrar plan status new
     */
    const REGPLAN_NEW = 1;

    /**
     * @const registrar plan status in progress
     */
    const REGPLAN_INPROGRESS = 2;

    /**
     * @const registrar plan status submitted
     */
    const REGPLAN_SUBMITTED = 3;

    /**
     * @const registrar plan status requires revision
     */
    const REGPLAN_REVISION = 4;

    /**
     * @const registrar plan status compliant
     */
    const REGPLAN_COMPLIANT = 5;

    /**
     * @const registrar plan status not yet compliant
     */
    const REGPLAN_NOTCOMPLIANT = 6;

    /**
     * @const registrar plan item status not applicable
     */
    const REGITEM_NOTAPPLICABLE = 1;

    /**
     * @const registrar plan item status not started
     */
    const REGITEM_NOTSTARTED = 2;

    /**
     * @const registrar plan item status in progress
     */
    const REGITEM_INPROGRESS = 3;

    /**
     * @const registrar plan item status completed
     */
    const REGITEM_COMPLETED = 4;


    /**
     * Save information for new training plan.
     * We get the data from the submitted form.
     *
     * @return boolean
     */
    public static function training_plan_add() {
        global $DB;

        $addplanform = new \local_evtp\forms\templateplanaddform();
        if ($data = $addplanform->get_data() and confirm_sesskey()) {
            $data->deleted = 0;
            return (boolean)$DB->insert_record('local_evtp_plan', $data);
        }
        return false;
    }

    /**
     * Update the submitted training plan information.
     * We get the data from the submitted form.
     *
     * @return boolean
     */
    public static function training_plan_edit() {
        global $DB;

        $editplanform = new \local_evtp\forms\templateplaneditform();
        if ($data = $editplanform->get_data() and confirm_sesskey()) {
            if (empty($data->active)) {
                $data->active = 0;
            }
            return (boolean)$DB->update_record('local_evtp_plan', $data);
        }
        return false;
    }

    /**
     * Delete the given plan.
     * Note we don't actually delete the plan, we simply mark it as deleted.
     *
     * @todo We should delete records if there are no associated registrar plans.
     *       Maybe we should off-load to a scheduled task to do clean-ups of the data.
     * @param int $planid
     * @return boolean
     */
    public static function training_plan_delete($planid) {
        global $DB;

        if (confirm_sesskey()) {
            $record = new \stdClass;
            $record->id = $planid;
            $record->deleted = 1;
            return $DB->update_record('local_evtp_plan', $record);
        }
        return false;
    }

    /**
     * Change the active status of a training plan.
     *
     * @param int $planid
     * @return boolean
     */
    public static function training_plan_change_status($planid) {
        global $DB;

        if ($record = $DB->get_record('local_evtp_plan', array('id' => $planid), 'id, active')) {
            $record->active = (($record->active + 1) % 2);
            return $DB->update_record('local_evtp_plan', $record);
        }
        return false;
    }

    /**
     * Duplicate a training plan.
     *
     * @param int $planid
     * @return void
     */
    public static function training_plan_duplicate($planid) {
        global $DB;

        if (confirm_sesskey() and ($plan = $DB->get_record('local_evtp_plan', array('id' => $planid)))) {
            unset($plan->id);

            // Update the start date if required.
            $now = time();
            if ($plan->startdate < $now) {
                $plan->startdate = $now;
            }

            // Change the name.
            $plan->name .= ' (copy)';

            // Create a new plan and get the id.
            if ($newid = $DB->insert_record('local_evtp_plan', $plan)) {

                // Now we need to duplicate any plan items.
                if ($items = $DB->get_records('local_evtp_item', array('planid' => $planid))) {

                    foreach ($items as $item) {
                        unset($item->id);
                        $item->planid = $newid;
                        $DB->insert_record('local_evtp_item', $item);
                    }
                }
            }
        }
    }
    
    /**
     * Update the submitted training plan item information.
     * We get the data from the submitted form.
     *
     * @return boolean
     */
    public static function training_plan_item_edit() {
        global $DB;

        // We need to set up the customdata for the form to aid in data validation.
        $planid = required_param('planid', PARAM_INT);
        $planname = $DB->get_field('local_evtp_plan', 'name', array('id' => $planid));
        $itemcount = $DB->count_records('local_evtp_item', array('planid' => $planid, 'deleted' => 0));
        $customdata = array('planname' => $planname,
                            'planid'   => $planid,
                            'itemcount' => $itemcount);
        $edititemform = new \local_evtp\forms\templateitemeditform(null, $customdata);

        if ($data = $edititemform->get_data() and confirm_sesskey()) {
            $data->description = $data->descriptioneditor['text'];
            $data->format      = $data->descriptioneditor['format'];

            // New record or existing?
            if (empty($data->id)) {
                unset($data->id);
                $newid = $DB->insert_record('local_evtp_item', $data);
            } else {
                $DB->update_record('local_evtp_item', $data);
            }

            // Re-sequence items if necessary.
            if (empty($data->id)) {
               if ($data->sequence == ($data->itemcount + 1)) { // New item has been added to the end.
                    // Do nothing.
                } else { // New item been added in the middle.
                    self::training_plan_items_resequence($data->sequence, $newid, true);
                }
            } else {
                $count = $DB->count_records('local_evtp_item', array('sequence' => $data->sequence));
                if ($count > 1) { // Moved to the middle.
                    self::training_plan_items_resequence($data->sequence, $data->id, true);
                }
            }

            self::resequence_plan_items($planid);
                
        }
        return false;
    }

    /**
     * Delete the given item.
     * Note we don't actually delete the item, we simply mark it as deleted.
     *
     * @todo We should delete records if there are no associated registrar plans.
     *       Maybe we should off-load to a scheduled task to do clean-ups of the data.
     *       See same note on training_plan_delete()
     * @param int $itemid
     * @return boolean
     */
    public static function training_plan_item_delete($itemid) {
        global $DB;

        $return = false;
        if (confirm_sesskey()) {
            $record = new \stdClass;
            $record->id = $itemid;
            $record->deleted = 1;
            $record->sequence = 0;
            $return = $DB->update_record('local_evtp_item', $record);

            self::resequence_plan_items($planid);
           
        }
        return $return;
    }

    /**
     * Change an item sequence number.
     *
     * @param integer $planid
     * @param integer $itemid
     * @param string $dir  which way to move, up or down
     * @return boolean
     */
    public static function training_plan_item_move($planid, $itemid, $dir) {
        global $DB;

        $return = false;
        // Get the item record. Also a sanity check.
        if ($item = $DB->get_record('local_evtp_item', array('id' => $itemid), 'id, sequence')) {
            // Sanity check that we can move it up.
            if (($dir == 'up') and ($item->sequence > 1)) {
                $item->sequence--;
                if ($DB->update_record('local_evtp_item', $item)) {
                    $return = self::training_plan_items_resequence($item->sequence, $itemid, true, true);
                }
            }
        
            // Get the current itemcount.
            $itemcount = $DB->count_records('local_evtp_item', array('planid' => $planid, 'deleted' => 0));

            // Sanity check that we can move it down.
            if (($dir == 'down') and ($item->sequence < $itemcount)) {
                $item->sequence++;
                if ($DB->update_record('local_evtp_item', $item)) {
                    $return=self::training_plan_items_resequence($item->sequence, $itemid, false, true);
                }
            }
            self::resequence_plan_items($planid);
        }

        return $return;
    }

    /**
     * Re-sequence items.
     *
     * @param integer $start  the starting sequence number
     * @param integer $ignoreid  (optional, default=0) item id to ignore
     * @param boolean $increase  (optional, default=true) if false then decrease numbers by 1
     * @param boolean $move  (optional, default=false) are we delaing with a move or a new item insert
     * @return void
     */
    public static function training_plan_items_resequence($start, $ignoreid=0, $increase=true, $move=false) {
        global $DB;
        
        $sql = "UPDATE {local_evtp_item} i
                SET i.sequence = ";
        $sql .= ($increase) ? "(i.sequence + 1) " : "(i.sequence - 1) ";
        $sql .= "WHERE deleted = 0";
        if (!empty($ignoreid)) {
            $sql .= " AND i.id <> :id";
        }

        if ($move) {
            $sql .= " AND i.sequence = :start";
        } else if ($increase) {
            $sql .= " AND i.sequence >= :start";
        } else {
            $sql .= " AND i.sequence <= :start";
        }

        $params = array('id' => $ignoreid, 'start' => $start);

        return $DB->execute($sql, $params);
    }

    /**
     * Return a list of registrar plans based on the submitted search criteria.
     *
     * @param object $data  submitted form data
     * @return array  of plan objects
     */
    public static function get_plans_from_search($data) {
        global $DB;

        $fullnamefields1 = get_all_user_name_fields(true, 'ru', '', 'reg');
        $fullnamefields2 = get_all_user_name_fields(true, 'mu', '', 'me');
        $sql = "SELECT rp.id, p.name, ru.id AS registraruserid, mu.id AS meuserid, 
                       rp.startdate, rp.completiondate, rp.status,
                       $fullnamefields1, $fullnamefields2
                FROM {local_evtp_regplan} rp
                JOIN {local_evtp_plan} p ON p.id=rp.planid
                JOIN {user} ru ON ru.id=rp.registraruserid
                JOIN {user} mu ON mu.id=rp.meuserid ";
        $params = array();
        $where = array();

        if (!empty($data->planid)) {
            $where[] = 'rp.planid = :planid';
            $params['planid'] = $data->planid;
        }

        if (!empty($data->meid)) {
            $where[] = 'rp.meuserid = :meid';
            $params['meid'] = $data->meid;
        }

        if (!empty($data->registrarid)) {
            $where[] = 'rp.registraruserid = :registrarid';
            $params['registrarid'] = $data->registrarid;
        }

        if (!empty($data->planstatus)) {
            $where[] = 'rp.status = :planstatus';
            $params['planstatus'] = $data->planstatus;
        }
    
        if (!empty($data->pathway)) {
            $pathways = self::get_pathways();
            $sql .= "JOIN {user_info_data} uidp ON uidp.userid = rp.registraruserid
                     JOIN {user_info_field} uifp ON uifp.id = uidp.fieldid ";
            $where[] = '((uifp.shortname = "pathways") AND (uidp.data = :uidpdata))';
            // The key value in data will be 1 greater due to array_merge in form class.
            $params['uidpdata'] = $pathways[($data->pathway - 1)];
        }

        if (!empty($data->region)) {
            $regions = self::get_regions();
            $sql .= "JOIN {user_info_data} uidr ON uidr.userid = rp.registraruserid
                     JOIN {user_info_field} uifr ON uifr.id = uidr.fieldid ";
            $where[] = '((uifr.shortname = "region") AND (uidr.data = :uidrdata))';
            // The key value in data will be 1 greater due to array_merge in form class.
            $params['uidrdata'] = $regions[($data->region - 1)];
        }

        if (!empty($data->cohort)) {
            $cohorts = self::get_cohorts();
            $sql .= "JOIN {user_info_data} uidc ON uidc.userid = rp.registraruserid
                     JOIN {user_info_field} uifc ON uifc.id = uidc.fieldid ";
            $where[] = '((uifc.shortname = "cohortyear") AND (uidc.data = :uidcdata))';
            // The key value in data will be 1 greater due to array_merge in form class.
            $params['uidcdata'] = $cohorts[($data->cohort - 1)];
        }

        if (!empty($where)) {
            $sql .= ' WHERE '.implode(' AND ',$where);
        }

        $sql .= ' ORDER BY p.name, ru.lastname, ru.firstname';

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Create a new registrar training plan based on the submitted form data.
     *
     * @param object $data  submitted form data from \local_evtp\forms\plancreationform
     * @return boolean  registrar plan successfully added?
     */
    public static function create_new_registrar_plan($data) {
        global $DB;

        // New registrar training plan record.
        $newrec = array('planid' => $data->planid,
                        'meuserid' => $data->meid,
                        'registraruserid' => $data->registrarid,
                        'status' => self::REGPLAN_NEW);
        if ($newrec['id'] = $DB->insert_record('local_evtp_regplan', $newrec)) {

            // Copy items from the plan template.
            $select = "(planid = :planid) AND (linetype <> :linetype) AND (deleted = 0)";
            $params = array('planid' => $data->planid, 'linetype' => self::ITEM_ELECTIVE);
            if ($items = $DB->get_records_select('local_evtp_item', $select, $params)) {
                foreach ($items as $item) {
                    $regitem = new \stdClass;
                    $regitem->regplanid = $newrec['id'];
                    $regitem->itemid = $item->id;
                    $regitem->name = $item->name;
                    $regitem->linetype = $item->linetype;
                    $regitem->description = $item->description;
                    $regitem->descriptionformat = $item->descriptionformat;
                    $regitem->sequence = $item->sequence;
                    $regitem->status = ($item->linetype == self::ITEM_MANDATORY) ? self::REGITEM_NOTSTARTED : self::REGITEM_NOTAPPLICABLE;
                    $DB->insert_record('local_evtp_regitem', $regitem);
                }
                self::resequence_registrar_plan_items($newrec['id']);
            }
            //TODO change this into an event and send notifcation from event handler.
            self::send_new_plan_notification($newrec);
            return $newrec['id'];
        }
        return false;
    }

    /**
     * Send a notification about the new registrar training plan.
     *
     * @todo finish this function.
     * @param object $regplan
     * @return void
     */
    public static function send_new_plan_notification($regplan) {
    }

    /**
     * Re-sequence the registrar training plan items.
     *
     * @param integer $regplanid
     * @return void
     */
    public static function resequence_registrar_plan_items($regplanid) {
        global $DB;

        if ($items = $DB->get_records('local_evtp_regitem', array('regplanid'=>$regplanid), 'sequence ASC', 'id, sequence')) {
            $count = 0;
            foreach ($items as $item) {
                $item->sequence = ++$count;
                $DB->update_record('local_evtp_regitem', $item);
            }
        }
    }

    /**
     * Re-sequence training plan items.
     *
     * @param integer $planid
     * @return void
     */
    public static function resequence_plan_items($planid) {
        global $DB;

        $select = "(planid = :planid) AND (deleted = 0)";
        $params = array('planid' => $planid);
        if ($items = $DB->get_records_select('local_evtp_item', $select, $params, 'sequence ASC', 'id, sequence')) {
            $count = 0;
            foreach ($items as $item) {
                $item->sequence = ++$count;
                $DB->update_record('local_evtp_item', $item);
            }
        }
    }

    /**
     * Return an array of current training plans.
     *
     * @todo caching
     * @param boolean $includeinactive  (optional, default true)
     * @return array
     */
    public static function get_training_plans($includeinactive=true, $includedeleted=true) {
        global $DB;

        $trainingplans = array();
        $strinactive = ' ('.get_string('inactive', 'local_evtp').')';

        $select = array();
        if (!$includeinactive) {
            $select[] = 'active = 1';
        }
        if (!$includedeleted) {
            $select[] = 'deleted = 0';
        }
        $selectsql = implode(' AND ', $select);
        if (!empty($selectsql)) {
            $plans = $DB->get_records_select('local_evtp_plan', $selectsql, null, 'name', 'id, name, active');
        } else {
            $plans = $DB->get_records('local_evtp_plan', null, 'name', 'id, name, active');
        }

        if (!empty($plans)) {
            foreach ($plans as $id=>$plan) {
                if ($plan->active == 1) {
                    $trainingplans[$id] = $plan->name;
                } else if ($includeinactive) {
                    $trainingplans[$id] = $plan->name.$strinactive;
                }
            }
        }
        return $trainingplans;
    }

    /**
     * Return an array of current MEs.
     *
     * @todo caching
     * @return array  userid => fullname
     */
    public static function get_mes() {
        $mes = array();

        $fullnamefields = get_all_user_name_fields(true, 'u');
        if ($users = get_users_by_capability(\context_system::instance(), 'local/evtp:me', 'u.id, '.$fullnamefields, 'u.lastname, u.firstname')) {
            foreach ($users as $user) {
                $mes[$user->id] = fullname($user);
            }
        }
        return $mes;
    }

    /**
     * Return an array of registrars ie all users.
     *
     * @todo caching
     * @return array  userid => fullname
     */
    public static function get_registrars() {
        global $DB;

        $registrars = array();

        $fullnamefields = get_all_user_name_fields(true);
        if ($users = $DB->get_records('user', array('deleted'=>0, 'suspended'=>0), 'lastname, firstname', 'id, username, '.$fullnamefields)) {
            foreach ($users as $user) {
                if ($user->username == 'guest') {
                    continue;
                }
                $registrars[$user->id] = fullname($user);
            }
        }
        return $registrars;
    }

    /**
     * Return an array of pathways from the custom profile fields.
     *
     * @todo caching
     * @return array  position number => pathway name
     */
    public static function get_pathways() {
        global $DB;

        $pathways = array();
        if ($options = $DB->get_field('user_info_field', 'param1', array('shortname' => 'pathways'))) {
            $pathways = explode("\n", $options);
        }
        return $pathways;
    }

    /**
     * Return an array of regions from the custom profile fields.
     *
     * @todo caching
     * @return array  position number => region name
     */
    public static function get_regions() {
        global $DB;

        $regions = array();
        if ($options = $DB->get_field('user_info_field', 'param1', array('shortname' => 'region'))) {
            $regions = explode("\n", $options);
        }
        return $regions;
    }

    /**
     * Return an array of cohorts from the custom profile fields.
     *
     * @todo caching
     * @return array  position number => region name
     */
    public static function get_cohorts() {
        global $DB;

        $cohorts = array();
        if ($options = $DB->get_field('user_info_field', 'param1', array('shortname' => 'cohortyear'))) {
            $cohorts = explode("\n", $options);
        }
        return $cohorts;
    }

    /**
     * Retrieve a list of elective items for a training plan that have not yet been assigned to the registrar plan.
     *
     * @param integer $regplanid
     * @return array
     */
     public static function get_unassigned_elective_items($regplanid) {
        global $DB;

        $sql = "SELECT x.id, x.name
                FROM (
                    SELECT i.id, i.name, COALESCE(ri.sequence, 0) AS seq
                    FROM {local_evtp_item} i
                    JOIN {local_evtp_plan} p ON p.id=i.planid
                    JOIN {local_evtp_regplan} rp ON rp.planid=p.id
                    LEFT JOIN {local_evtp_regitem} ri ON ri.itemid=i.id
                    WHERE rp.id= :regplanid
                        AND i.deleted=0
                        AND i.linetype = :linetype
                ) x
                WHERE x.seq = 0";
        $params = array('regplanid'=>$regplanid, 'linetype'=>self::ITEM_ELECTIVE);

        return $DB->get_records_sql_menu($sql, $params);
     }

    /**
     * Return an array of the human readable item status.
     *
     * @return array
     */
    public static function get_item_statuses() {
        return array(
            self::ITEM_HEADER    => get_string('statusitemheader', 'local_evtp'),
            self::ITEM_MANDATORY => get_string('statusitemmandatory', 'local_evtp'),
            self::ITEM_ELECTIVE  => get_string('statusitemelective', 'local_evtp')
        );
    }

    /**
     * Return an array of the human readable registrar plan statuses
     *
     * @return array
     */
    public static function get_registrarplan_statuses() {
        return array(
            self::REGPLAN_NEW          => get_string('statusregplannew', 'local_evtp'),
            self::REGPLAN_INPROGRESS   => get_string('statusregplaninprogress', 'local_evtp'),
            self::REGPLAN_SUBMITTED    => get_string('statusregplansubmitted', 'local_evtp'),
            self::REGPLAN_REVISION     => get_string('statusregplanrevision', 'local_evtp'),
            self::REGPLAN_COMPLIANT    => get_string('statusregplancompliant', 'local_evtp'),
            self::REGPLAN_NOTCOMPLIANT => get_string('statusregplannotcompliant', 'local_evtp')
        );
    }

    /**
     * Return an array of the human readable registrar item statuses
     *
     * @return array
     */
    public static function get_registraritem_statuses() {
        return array(
            self::REGITEM_NOTAPPLICABLE => get_string('statusregitemnotapplicable', 'local_evtp'),
            self::REGITEM_NOTSTARTED    => get_string('statusregitemnotstarted', 'local_evtp'),
            self::REGITEM_INPROGRESS    => get_string('statusregiteminprogress', 'local_evtp'),
            self::REGITEM_COMPLETED     => get_string('statusregitemcompleted', 'local_evtp')
        );
    }

}
