<?php
/**
 * EVTP Training Plans
 *
 * Container for registrar training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evtp;

defined('MOODLE_INTERNAL') || die();

/**
 * Container for registrar training plan.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class regplan {
    
    /** @var integer $id */
    private $id;

    /** @var string $name */
    private $name;

    /** @var stdclass $meuser  user object for associated ME */
    private $meuser;

    /** @var stdclass $registraruser  user object for associated registrar */
    private $registraruser;

    /** @var integer $status  status of the plan */
    private $status;

    /** @var integer $startdate  startdate for plan */
    private $startdate;

    /** @var integer $completiondate  completiondate for plan */
    private $completiondate;

    /** @var array $logs  array of objects of the associated log entries */
    private $logs;

    /** @var integer $lastreviewdate  last review log date */
    private $lastreviewdate;

    /** @var string $lastreview  last review comment */
    private $lastreview;

    /** @var stdclass $lastreviewer  last review comment user object */
    private $lastreviewer;

    /** @var array $items  the associated item objects */
    private $items = array();

    /**
     * Constructor
     *
     * @param integer $id
     * @return void
     */
    public function __construct($id) {
        global $DB;

        $this->_load_object($id);
    }

    /**
     * Load up the object.
     *
     * @param integer $id
     */
    private function _load_object($id) {
        global $DB;

        if ($plan = $DB->get_record('local_evtp_regplan', array('id' => $id))) {
            // Load up this object.
            $this->set_id($id);
            $this->set_name($plan->planid);
            $this->set_meuser($plan->meuserid);
            $this->set_registraruser($plan->registraruserid);
            $this->set_status($plan->status);
            if (isset($plan->startdate)) {
                $this->set_startdate($plan->startdate);
            } else {
                $this->set_startdate(0);
            }
            if (isset($plan->completiondate)) {
                $this->set_completiondate($plan->completiondate);
            } else {
                $this->set_completiondate(0);
            }
            $this->set_logs($id);
            $this->set_items($id);
        }
    }

    /**
     * Is there a valid plan loaded?
     *
     * @return boolean
     */
    public function is_valid() {
        return (!empty($this->id));
    }

    /**
     * Is current user the registrar?
     *
     * @uses $USER
     * @return boolean
     */
    public function is_registrar() {
        global $USER;

        return ($this->registraruser->id == $USER->id);
    }

    /**
     * Is current user the ME?
     *
     * @uses $USER
     * @return boolean
     */
    public function is_me() {
        global $USER;

        return ($this->meuser->id == $USER->id);
    }

    /**
     * Is current user a training plan admin?
     *
     * @return boolean
     */
    public function is_admin() {
        return has_capability('local/evtp:manage', \context_system::instance());
    }

    /**
     * Submit the plan for review.
     * Refer to 3.5.4 of specification.
     *
     * @return boolean
     */
    public function submit_for_review() {
        if (!$this->is_registrar()) { 
            return false;
        }

        $data = new \stdClass;
        $data->status = \local_evtp\utils::REGPLAN_SUBMITTED;
        
        return ($this->update_plan($data) and $this->add_new_log($data));
    }

    /**
     * Add a new status log to the registrar plan.
     *
     * @param object $data
     * @return boolean
     */
    public function add_new_log($data) {
        global $DB, $USER;

        $log = new \stdClass;
        $log->regplanid    = $this->id;
        $log->status       = empty($data->status) ? $this->status : $data->status;
        $log->userid       = empty($data->userid) ? $USER->id : $data->userid;
        $log->modifiedtime = empty($data->modifiedtime) ? time() : $data->modifiedtime;
        if (!empty($data->comment)) {
            $log->comment = $data->comment;
        } else if (!empty($data->commentseditor['text'])) {
            $log->comment = $data->commentseditor['text'];
        } else {
            $log->comment = '';
        }

        if ($DB->insert_record('local_evtp_status', $log)) {
            // Update the registrar plan record as well.
            $regplan = $DB->get_record('local_evtp_regplan', array('id'=>$this->id), 'id, status, startdate, completiondate');
            $regplan->status = $log->status;
            if (empty($regplan->startdate)) {
                $regplan->startdate = time();
            }
            if ($log->status == \local_evtp\utils::REGPLAN_COMPLIANT) {
                $regplan->completiondate = time();
            } else {
                $regplan->completiondate = 0;
            }
            $DB->update_record('local_evtp_regplan', $regplan);

            $this->_load_object($this->id);
            
            return true;
        }

        return false;
    }

    /**
     * Remove a status log entry.
     *
     * @param integer $logid
     * @return void
     */
    public function remove_log($logid) {
        global $DB;

        $DB->delete_records('local_evtp_status', array('id'=>$logid));
        $this->_load_object($this->id);
    }

    /**
     * Remove an item from teh training plan.
     *
     * @param integer $itemid
     * @return boolean
     */
    public function delete_item_from_plan($itemid) {
        global $DB;

        if (confirm_sesskey()) {
            return $DB->delete_records('local_evtp_regitem', array('regplanid'=>$this->id, 'id'=>$itemid));
        }
        return false;
    }

    /**
     * Add a new item to the registrar plan.
     *
     * @param integer $itemid
     * @return boolean
     */
    public function add_new_item_to_plan($itemid) {
        global $DB;

        // Sanity check - does record already exist?
        if ($DB->record_exists('local_evtp_regitem', array('regplanid'=>$this->id, 'itemid'=>$itemid))) {
            return false;
        }

        if ($item = $DB->get_record('local_evtp_item', array('id'=>$itemid))) {
            $count = $DB->count_records('local_evtp_regitem', array('regplanid'=>$this->id));
            $regitem = new \stdClass;
            $regitem->regplanid         = $this->id;
            $regitem->itemid            = $itemid;
            $regitem->name              = $item->name;
            $regitem->linetype          = $item->linetype;
            $regitem->description       = $item->description;
            $regitem->descriptionformat = $item->descriptionformat;
            $regitem->sequence          = (++$count);
            $regitem->status            = \local_evtp\utils::REGITEM_NOTSTARTED;
            return (boolean)$DB->insert_record('local_evtp_regitem', $regitem);
        } else {
            return false;
        }
    }

    /**
     * Update an item with the supplied data from \local_evtp\forms\planitemform.
     *
     * @param object $data
     * @return void
     */
    public function update_item($data) {
        global $DB;

        // Do some cleaning of the submitted form data.
        if (!empty($data->mecommentseditor)) {
            $data->mecomments = $data->mecommentseditor['text'];
            $data->mecommentsformat = $data->mecommentseditor['format'];
            unset($data->mecommentseditor);
        }
        if (!empty($data->plannedplacementeditor)) {
            $data->plannedplacement = $data->plannedplacementeditor['text'];
            $data->plannedplacementformat = $data->plannedplacementeditor['format'];
            unset($data->plannedplacementeditor);
        }
        if (!empty($data->registrarcommentseditor)) {
            $data->registrarcomments = $data->registrarcommentseditor['text'];
            $data->registracommentsformat = $data->registrarcommentseditor['format'];
            unset($data->registrarcommentseditor);
        }
        unset($data->submitbutton);

        $DB->update_record('local_evtp_regitem', $data);
    }

    /**
     * Retrieve a the status log entry for the given id
     *
     * @param integer $logid
     * @return object
     */
    public function get_log_by_id($logid) {
        if (!empty($this->logs[$logid])) {
            return $this->logs[$logid];
        }
        return null;
    }

    /**
     * Retrieve the plan id.
     *
     * @return integer
     */
    public function get_id() {
        return (integer)$this->id;
    }

    /**
     * Retrieve the plan name.
     *
     * @return string
     */
    public function get_name() {
        return (string)$this->name;
    }

    /**
     * Retrieve the registrar user object.
     *
     * @return object
     */
    public function get_registrar() {
        return (object)$this->registraruser;
    }

    /**
     * Retrieve the ME user object.
     *
     * @return object
     */
    public function get_me() {
        return (object)$this->meuser;
    }

    /**
     * Retrieve the registrar plan status.
     *
     * @return string
     */
    public function get_status() {
        $statuses = \local_evtp\utils::get_registrarplan_statuses();
        return $statuses[$this->status];
    }

    /**
     * Retrieve the registrar plan status value (for forms).
     *
     * @return integer
     */
    public function get_status_value() {
        return (integer)$this->status;
    }

    /**
     * Retrieve the last review data.
     *
     * @return string
     */
    public function get_lastreviewdate() {
        return (empty($this->lastreviewdate)) ? get_string('neverreviewed', 'local_evtp') : userdate($this->lastreviewdate, get_string('strftimedate', 'langconfig'));
    }

    /**
     * Retrieve the last review comments.
     *
     * @return string
     */
    public function get_lastreview() {
        return (string)$this->lastreview;
    }

    /**
     * Retrieve a list of the status logs in a suitable list format for display.
     *
     * @return array
     */
    public function get_logs_list() {
        global $DB;

        $logs = array();
        $statuses = \local_evtp\utils::get_registrarplan_statuses();
        $usernamefields = get_all_user_name_fields(true);

        if (!empty($this->logs)) {
            foreach ($this->logs as $log) {
                $l = new \stdClass;
                $l->id = $log->id;
                $l->modifiedtime = userdate($log->modifiedtime, get_string('strftimedate', 'langconfig'));
                $l->status = $statuses[$log->status];
                if ($user = $DB->get_record('user', array('id'=>$log->userid), "id, $usernamefields")) {
                    $l->who = fullname($user);
                } else {
                    $l->who = '';
                }
                $l->comment = $log->comment;

                $logs[$log->id] = $l;
            }
        }
        return $logs;
    }

    /**
     * Retrieve a single item for the given id.
     *
     * @param integer $itemid
     * @return object
     */
    public function get_item($itemid) {
        if (!empty($this->items[$itemid])) {
            return $this->items[$itemid];
        } else {
            return null;
        }
    }

    /**
     * Retrieve a list of the training items in a suitable list format for display.
     *
     * @return array
     */
    public function get_items_list() {
        $items = array();
        $statuses = \local_evtp\utils::get_registraritem_statuses();

        $stryes = get_string('yes', 'local_evtp');
        $strno  = get_string('no', 'local_evtp');
        $strna  = get_string('na', 'local_evtp');

        foreach ($this->items as $item) {
            $i = new \stdClass;
            $i->id      = $item->id;
            $i->name    = $item->name;
            $i->heading = ($item->linetype == \local_evtp\utils::ITEM_HEADER);

            // If a heading item then we leave everything else blank.
            if ($i->heading) {
                $i->status            = '';
                $i->startdate         = '';
                $i->completiondate    = '';
                $i->plannedplacement  = '';
                $i->registrarcomments = '';
                $i->mecomments        = '';
                $i->compliant         = '';
                $i->description       = $item->description;
                $i->linetype          = $item->linetype;
            } else {
                $i->status            = $statuses[$item->status];
                $i->startdate         = (empty($item->startdate)) ? '' : userdate($item->startdate, get_string('strftimedate', 'langconfig'));
                $i->completiondate    = (empty($item->completiondate)) ? '' : userdate($item->completiondate, get_string('strftimedate', 'langconfig'));
                $i->plannedplacement  = $item->plannedplacement;
                $i->registrarcomments = $item->registrarcomments;
                $i->mecomments        = $item->mecomments;
                $i->description       = $item->description;
                $i->linetype          = $item->linetype;
                if ($item->status == \local_evtp\utils::REGITEM_COMPLETED) {
                    $i->compliant = $stryes;
                } else if ($item->status == \local_evtp\utils::REGITEM_NOTAPPLICABLE) {
                    $i->compliant = $strna;
                } else {
                    $i->compliant = $strno;
                }
            }

            $items[$item->id] = $i;
        }

        return $items;
    }

    /**
     * Update the plan.
     *
     * @param object $data
     * @return boolean
     */
    protected function update_plan($data) {
        global $DB;

        if (empty($data)) {
            return false;
        }

        $data->id = $this->id;

        return $DB->update_record('local_evtp_regplan', $data);
    }

    /**
     * Set the id.
     *
     * @param integer $id
     * @return void
     */
    public function set_id($id) {
        $this->id = $id;
    }

    /**
     * Set the plan name from the template plan.
     *
     * @param integer $planid
     * @return void
     */
    public function set_name($planid) {
        global $DB;

        if ($name = $DB->get_field('local_evtp_plan', 'name', array('id'=>$planid))) {
            $this->name = $name;
        }
    }

    /**
     * Set the meuser object.
     *
     * @param integer $meuserid
     * @return void
     */
    public function set_meuser($meuserid) {
        global $DB;

        if ($meuser = $DB->get_record('user', array('id' => $meuserid))) {
            $this->meuser = $meuser;
        }
    }

    /**
     * Set the registraruser object.
     *
     * @param integer $registraruserid
     * @return void
     */
    public function set_registraruser($registraruserid) {
        global $DB;

        if ($registraruser = $DB->get_record('user', array('id' => $registraruserid))) {
            $this->registraruser = $registraruser;
        }
    }

    /**
     * Set the plan status.
     *
     * @param integer $status
     * @return void
     */
    public function set_status($status) {
        $this->status = $status;
    }

    /**
     * Set the startdate.
     *
     * @param integer $startdate
     * @return void
     */
    public function set_startdate($startdate) {
        $this->startdate = $startdate;
    }

    /**
     * Set the completiondate.
     *
     * @param integer $completiondate
     * @return void
     */
    public function set_completiondate($completiondate) {
        $this->completiondate = $completiondate;
    }

    /**
     * Set the plan logs.
     *
     * @param integer $planid
     * @return void
     */
    public function set_logs($planid) {
        global $DB;

        if ($logs = $DB->get_records('local_evtp_status', array('regplanid'=>$planid), 'modifiedtime ASC')) {
            $this->logs = $logs;

            $lastlog = array_pop($logs);

            $this->lastreviewdate = $lastlog->modifiedtime;
            $this->lastreview     = $lastlog->comment;

            if ($lastreviewer = $DB->get_record('user', array('id' => $lastlog->userid))) {
                $this->lastreviewer = $lastreviewer;
            }
        }
    }

    /**
     * Set the plan items.
     *
     * @param integer $planid
     * @return void
     */
    public function set_items($planid) {
        global $DB;

        if ($items = $DB->get_records('local_evtp_regitem', array('regplanid' => $planid), 'sequence ASC')) {
            $this->items = $items;
        }
    }
}
