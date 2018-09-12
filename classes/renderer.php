<?php
/**
 * EVTP Training Plans
 *
 * Output renderers.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define output renderers for this plugin.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */
class local_evtp_renderer extends plugin_renderer_base {

    /**
     * Training plan list screen.
     * Refer to 3.1 of specification.
     *
     * @return string
     */
    public function template_plan_list() {
        global $CFG, $DB;

        // Add new training plan form.
        $out = self::heading(get_string('addnewtrainingplan', 'local_evtp'));

        $addnewplanform = new \local_evtp\forms\templateplanaddform();

        ob_start();
        $addnewplanform->display();
        $out .= ob_get_contents();
        ob_end_clean();

        // Existing training plans.
        $out .= self::heading(get_string('existingtrainingplans', 'local_evtp'));

        if ($plans = $DB->get_records('local_evtp_plan', array('deleted' => 0), 'name ASC')) {
            
            $table = new html_table();
            $table->head = array(get_string('trainingplan', 'local_evtp'),
                                 get_string('active', 'local_evtp'),
                                 get_string('startdate', 'local_evtp'),
                                 get_string('action', 'local_evtp'));
            $table->align = array('left', 'center', 'right', 'center');
            $table->id = 'templateplanlist';
            $table->data = array();

            foreach ($plans as $plan) {
                $row = new html_table_row();

                // Delete icon and link.
                $delete = html_writer::link(
                    new moodle_url('/local/evtp/templateplanlist.php', array('planid' => $plan->id,
                                                                      'action' => 'del')),
                    self::pix_icon('t/delete',
                        get_string('deleteplan', 'local_evtp'),
                        'moodle',
                        array('class' => 'iconsmall'))
                );
                
                // Duplicate icon and link.
                $duplicate = html_writer::link(
                    new moodle_url('/local/evtp/templateplanlist.php', array('planid' => $plan->id,
                                                                      'action' => 'dup',
                                                                      'sesskey' => sesskey())),
                    self::pix_icon('t/copy',
                        get_string('duplicateplan', 'local_evtp'),
                        'moodle',
                        array('class' => 'iconsmall'))
                );

                // Plan edit link.
                $planlink = html_writer::link(
                    new moodle_url('/local/evtp/templateplanedit.php', array('id' => $plan->id)),
                    $plan->name);

                // Plan active checkbox.
                $activelink = $CFG->wwwroot.'/local/evtp/templateplanlist.php?planid='.$plan->id.'&action=active';
                $activecheckbox = html_writer::checkbox('active_'.$plan->id,
                                                        1,
                                                        ($plan->active == 1),
                                                        '',
                                                        array('onclick' => "window.location='".$activelink."'"));

                // Put the row together.
                $row->cells = array($planlink,
                                    $activecheckbox,
                                    userdate($plan->startdate, get_string('strftimedate', 'langconfig')),
                                    $delete.$duplicate);
                $table->data[] = $row;
            }

            $out .= html_writer::table($table);
            
        } else {
            $out .= self::notify_message(get_string('noexistingtrainingplans', 'local_evtp'));
        }

        return $out;
    }

    /**
     * Training plan delete confirmation screen.
     * Refer to 3.1 of specification.
     *
     * @param integer $planid  training plan id
     * @return string|boolean
     */
    public function training_plan_delete_confirmation($planid) {
        global $DB;

        if ($planname = $DB->get_field('local_evtp_plan', 'name', array('id' => $planid))) {
            $message = get_string('confirmdeleteplan', 'local_evtp', $planname);
            $continue = new moodle_url('/local/evtp/templateplanlist.php', array('planid'  => $planid,
                                                                          'action'  => 'del',
                                                                          'confirm' => 1,
                                                                          'sesskey' => sesskey()));
            $cancel = new moodle_url('/local/evtp/templateplanlist.php');

            return self::confirm($message, $continue, $cancel);
        }
        return false;
    }

    /**
     * Training plan edit screen.
     * Refer to 3.2 of specification.
     *
     * @param integer $planid  training plan id
     * @return string
     */
    public function template_plan_edit($planid) {
        global $DB;

        // Add new training plan form.
        $out = self::heading(get_string('trainingplanedit', 'local_evtp'));

        // Check if we can access the plan.
        $plan = $DB->get_record('local_evtp_plan', array('id' => $planid));

        $editplanform = new \local_evtp\forms\templateplaneditform(null, array('startdate' => $plan->startdate));
        $editplanform->set_data($plan);

        ob_start();
        $editplanform->display();
        $out .= ob_get_contents();
        ob_end_clean();

        // Training plan items.
        $out .= self::heading(get_string('trainingplanitems', 'local_evtp'));

        if ($items = $DB->get_records('local_evtp_item', array('planid' => $plan->id, 'deleted' => 0), 'sequence')) {

            $statuses = \local_evtp\utils::get_item_statuses();
            
            $table = new html_table();
            $table->head = array(get_string('itemname', 'local_evtp'),
                                 get_string('type', 'local_evtp'),
                                 get_string('action', 'local_evtp'));
            $table->align = array('left', 'center', 'center');
            $table->id = 'trainingplanitemlist';
            $table->data = array();

            // Set up variables for the up/down links.
            $count = 0;
            $itemcount = $DB->count_records('local_evtp_item', array('planid' => $planid, 'deleted' => 0));

            // Cycle through the available items.
            foreach ($items as $item) {
                $count++;

                $row = new html_table_row();

                // Set the item edit link.
                $itemlink = html_writer::link(
                    new moodle_url('/local/evtp/templateitemedit.php', array('planid' => $plan->id,
                                                                      'id' => $item->id)),
                    $item->name);

                // Delete icon and link.
                $delete = html_writer::link(
                    new moodle_url('/local/evtp/templateplanedit.php', array('id'     => $plan->id,
                                                                      'itemid' => $item->id,
                                                                      'action' => 'del')),
                    self::pix_icon('t/delete',
                        get_string('deleteitem', 'local_evtp'),
                        'moodle',
                        array('class' => 'iconsmall'))
                );

                // Up icon link.
                if ($count == 1) {
                    $up = self::pix_icon('t/up',
                        '',
                        'moodle',
                        array('class' => 'iconsmall invisible')
                    );
                } else {
                    $up = html_writer::link(
                        new moodle_url('/local/evtp/templateplanedit.php', array('id'     => $plan->id,
                                                                         'itemid' => $item->id,
                                                                         'action' => 'up')),
                        self::pix_icon('t/up',
                            get_string('moveup', 'local_evtp'),
                            'moodle',
                            array('class' => 'iconsmall'))
                    );
                }
 
                // Down icon link.
                if ($count == $itemcount) {
                    $down = self::pix_icon('t/down',
                        '',
                        'moodle',
                        array('class' => 'iconsmall invisible')
                    );
                } else {
                    $down = html_writer::link(
                        new moodle_url('/local/evtp/templateplanedit.php', array('id'     => $plan->id,
                                                                         'itemid' => $item->id,
                                                                         'action' => 'down')),
                        self::pix_icon('t/down',
                            get_string('movedown', 'local_evtp'),
                            'moodle',
                            array('class' => 'iconsmall'))
                    );
                }

                // Put the row together.
                $row->cells = array($itemlink,
                                    $statuses[$item->linetype],
                                    $delete.$up.$down);

                $table->data[] = $row;
            }

            $out .= html_writer::table($table);
            
        } else {
            $out .= self::notify_message(get_string('noexistingtrainingitems', 'local_evtp'));
        }

        $out .= html_writer::link(
            new moodle_url('/local/evtp/templateitemedit.php', array('id' => 0,
                                                              'planid' => $planid)),
            get_string('newtrainingplanitem', 'local_evtp'));

        return $out;
    }

    /**
     * Training plan item delete confirmation screen.
     * Refer to 3.2 of specification.
     *
     * @param integer $itemid  training plan item id
     * @return string
     */
    public function training_plan_item_delete_confirmation($planid, $itemid) {
        global $DB;

        if ($itemname = $DB->get_field('local_evtp_item', 'name', array('id' => $itemid))) {
            $message = get_string('confirmdeleteitem', 'local_evtp', $itemname);
            $continue = new moodle_url('/local/evtp/templateplanedit.php', array('id'  => $planid,
                                                                          'itemid'  => $itemid,
                                                                          'action'  => 'del',
                                                                          'confirm' => 1,
                                                                          'sesskey' => sesskey()));
            $cancel = new moodle_url('/local/evtp/templateplanedit.php', array('id' => $planid));

            return self::confirm($message, $continue, $cancel);
        }
        return false;
    }

    /**
     * Training plan item edit screen.
     * Refer to 3.3 of specification.
     *
     * @param integer $itemid  training plan item id
     * @param integer $planid  training plan id
     * @return string
     */
    public function training_plan_item_edit($itemid, $planid) {
         global $DB;

        $out = self::heading(get_string('trainingplanitemedit', 'local_evtp'));

        // Check if we can access the item.
        if (!empty($itemid)) {
            $item = $DB->get_record('local_evtp_item', array('id' => $itemid));
            $item->descriptioneditor = array('text' => $item->description);
        }

        // We must be able to access the plan.
        $planname = $DB->get_field('local_evtp_plan', 'name', array('id' => $planid));

        // Get the itemcount for this plan.
        $itemcount = $DB->count_records('local_evtp_item', array('planid' => $planid, 'deleted' => 0));

        // Add new training plan form.

        // Set the form customdata.
        $customdata = array('planname' => $planname,
                            'planid'   => $planid,
                            'itemcount' => $itemcount);
        $edititemform = new \local_evtp\forms\templateitemeditform(null, $customdata);
        if (!empty($item)) {
            $edititemform->set_data($item);
        }

        ob_start();
        $edititemform->display();
        $out .= ob_get_contents();
        ob_end_clean();

        return $out;
    }

    /**
     * Training plan search screen.
     * Refer to 3.4 of specification.
     *
     * @return string
     */
    public function training_plan_search() {
        global $USER;

        $out = self::heading(get_string('trainingplansearch', 'local_evtp'));

        $context = context_system::instance();
        $adminuser = has_capability('local/evtp:manage', $context);
        $meuser = has_capability('local/evtp:me', $context);

        // Only show search form to MEs and admins.
        if ($adminuser or $meuser) {
            $plansearchform = new \local_evtp\forms\plansearchform();

            ob_start();
            $plansearchform->display();
            $out .= ob_get_contents();
            ob_end_clean();

            if (!$data = $plansearchform->get_data()) {
                $data = null;
            }

        // Registrars only see their own plans.
        } else {
            $data = new \stdClass;
            $data->registrarid = $USER->id;
        }

        if ($plans = \local_evtp\utils::get_plans_from_search($data)) {
            $table = new html_table();
            $table->head = array(get_string('trainingplan', 'local_evtp'),
                    get_string('registrar', 'local_evtp'),
                    get_string('me', 'local_evtp'),
                    get_string('startdate', 'local_evtp'),
                    get_string('completedate', 'local_evtp'),
                    get_string('status', 'local_evtp'));
            $table->align = array('left', 'left', 'left', 'center', 'center', 'left');
            $table->id = 'registrarplanlist';
            $table->data = array();

            $registrars = array();
            $mes = array();
            $fullnamefieldsreg = get_all_user_name_fields(false, '', 'reg');
            $fullnamefieldsme = get_all_user_name_fields(false, '', 'me');
            $statuses = \local_evtp\utils::get_registrarplan_statuses();

            foreach ($plans as $plan) {
                $row = new html_table_row();

                // Set up the plan view link.
                $planlink = html_writer::link(
                        new moodle_url('/local/evtp/planview.php', array('id' => $plan->id)),
                        $plan->name);

                $startdate = (!empty($plan->startdate)) ? userdate($plan->startdate) : '-';

                $completiondate = (!empty($plan->completiondate)) ? userdate($plan->completiondate) : '-';

                if (empty($registrars[$plan->registraruserid])) {
                    $reguser = new stdClass;
                    foreach ($fullnamefieldsreg as $key=>$value) {
                        $reguser->$key = $plan->$value;
                    }
                    $reguser->id = $plan->registraruserid;
                    $registrars[$plan->registraruserid] = fullname($reguser);
                }

                if (empty($mes[$plan->meuserid])) {
                    $meuser = new stdClass;
                    foreach ($fullnamefieldsme as $key=>$value) {
                        $meuser->$key = $plan->$value;
                    }
                    $meuser->id = $plan->meuserid;
                    $mes[$plan->meuserid] = fullname($meuser);
                }

                $row->cells = array($planlink, $registrars[$plan->registraruserid], $mes[$plan->meuserid], $startdate, $completiondate, $statuses[$plan->status]);

                $table->data[] = $row;
            }

            $out .= html_writer::table($table);

        } else {
            $out .= self::notify_message(get_string('noplansmatchingsearchcriteria', 'local_evtp'));
        }

        if (has_capability('local/evtp:manage', $context)) {
            $out .= html_writer::empty_tag('hr');
            $out .= html_writer::link(new moodle_url('/local/evtp/plancreate.php'), get_string('createnewtrainingplan', 'local_evtp'));
        }

        return $out;
    }

    /**
     * Registrar training plan creation screen.
     * Refer to 3.4 of specification.
     * NB This has been separated out from the search screen and therefore
     *    is not specifically mentioned in specification.
     *
     * @return string
     */
    public function training_plan_create() {
        $plansearchurl = new moodle_url('/local/evtp/plansearch.php');

        $out = self::heading(get_string('createnewtrainingplan', 'local_evtp'));

        // Add a new registrar training plan creation form.
        $plancreationform = new \local_evtp\forms\plancreationform();

        if ($plancreationform->is_cancelled()) {
            redirect($plansearchurl);
            exit;
        }

        if ($data = $plancreationform->get_data()) {
            if ($planid = \local_evtp\utils::create_new_registrar_plan($data)) {
                redirect(new moodle_url('/local/evtp/planview.php', array('id'=>$planid)),
                         get_string('addednewregistrarplan', 'local_evtp'));
                exit;
            } else {
                $out .= self::notify_problem(get_string('erroraddingnewregistrarplan', 'local_evtp'));
            }
        }

        ob_start();
        $plancreationform->display();
        $out .= ob_get_contents();
        ob_end_clean();

        return $out;
    }

    /**
     * Registrar training plan view screen.
     * Refer to 3.5 of specification.
     *
     * @param \local_evtp\regplan $regplan
     * @return string
     */
    public function training_plan_view(\local_evtp\regplan $regplan) {

        // Sanity check. This should have been caught earlier but good to check here as well.
        if (!$regplan->is_valid()) {
            redirect(new moodle_url('/local/evtp/plansearch.php'), get_string('invalidplanid', 'local_evtp'));
            exit;
        }

        $regplanid = $regplan->get_id();

        $out = self::heading(get_string('trainingplanview', 'local_evtp'));

        // Set up a table of registrar plan information.
        $table = new html_table();
        $table->head = array();
        $table->align = array('left', 'left');
        $table->id = 'registrarplanview';
        $table->data = array();

        $table->data[] = array(get_string('trainingplan', 'local_evtp'),
                               $regplan->get_name());

        $table->data[] = array(get_string('registrar', 'local_evtp'),
                               fullname($regplan->get_registrar()));

        $table->data[] = array(get_string('me', 'local_evtp'),
                               fullname($regplan->get_me()));

        $table->data[] = array(get_string('status', 'local_evtp'),
                               $regplan->get_status());

        $table->data[] = array(get_string('lastsubmittedforreview', 'local_evtp'),
                               $regplan->get_lastreviewdate());

        $comments = $regplan->get_lastreview();
        if (empty($comments)) {
            $comments = get_string('nocomments', 'local_evtp');
        }
        $table->data[] = array(get_string('lastreviewcomments', 'local_evtp'),
                               $comments);

        $out .= html_writer::table($table);

        if ($regplan->is_me() or $regplan->is_admin()) {
            $out .= html_writer::empty_tag('hr');
            $out .= html_writer::link(
                new moodle_url('/local/evtp/planstatus.php', array('id'=>$regplanid)),
                get_string('updatetrainingplanstatus', 'local_evtp'));
        }

        if ($regplan->is_registrar()) {
            $out .= html_writer::empty_tag('hr');
            $out .= html_writer::link(
                new moodle_url('/local/evtp/planview.php',
                    array('id'=>$regplanid, 'review'=>1)),
                    get_string('submitplanforreview', 'local_evtp'));
        }
            

        $out .= html_writer::empty_tag('hr');
        $out .= self::heading(get_string('trainingitems', 'local_evtp'));

        // Display the associated training items.
        if ($items = $regplan->get_items_list()) {
            $table = new html_table();
            $table->head = array(get_string('itemname', 'local_evtp'),
                                 get_string('status', 'local_evtp'),
                                 get_string('plannedcompletiondate', 'local_evtp'),
                                 get_string('plannedplacement', 'local_evtp'),
                                 get_string('registrarcomments', 'local_evtp'),
                                 get_string('mecomments', 'local_evtp'),
                                 get_string('compliant', 'local_evtp'),
                                 '');
            $table->align = array('left', 'left', 'left', 'left', 'left', 'left', 'left','');
            $table->wrap = array(true, true, true, false, false, false, true, false);
            $table->id = 'registrarplanitems';
            $table->data = array();

            foreach ($items as $id=>$item) {
                $row = new html_table_row();

                // Create a heading or a link.
                if ($item->heading) {
                    $itemlink = html_writer::tag('strong', $item->name);
                    $itemdesc = html_writer::tag('div', $item->description);
                    $itemcell = new html_table_cell($itemlink.$itemdesc);
                    $itemcell->colspan = 8;

                    $row->cells = array($itemcell);
                    
                } else {
                    $itemlink = html_writer::link(
                        new moodle_url('/local/evtp/planitem.php',
                                       array('id'=>$id, 'regplanid'=>$regplanid)),
                        $item->name);

                    if ($item->linetype == \local_evtp\utils::ITEM_ELECTIVE) {
                        $delete = html_writer::link(
                            new moodle_url('/local/evtp/planview.php',
                                           array('id' => $regplanid,
                                                 'delitem' => $id)),
                            self::pix_icon('t/delete',
                                            get_string('deleteitem', 'local_evtp'),
                                            'moodle',
                                            array('class' => 'iconsmall'))
                        );
                    } else {
                        $delete = '';
                    }

                    $row->cells = array($itemlink,
                                        $item->status,
                                        $item->completiondate,
                                        $item->plannedplacement,
                                        $item->registrarcomments,
                                        $item->mecomments,
                                        $item->compliant,
                                        $delete);
                }

                $table->data[] = $row;
            }
            $out .= html_writer::table($table);
        }

        $out .= html_writer::empty_tag('hr');

        // Add elective items dropdown.
        if (($electiveoptions = \local_evtp\utils::get_unassigned_elective_items($regplanid)) and ($regplan->is_admin() or $regplan->is_registrar())) {
            $out .= self::heading(get_string('addelectiveitem', 'local_evtp'), 4);
            $out .= $this->single_select(new moodle_url('/local/evtp/planview.php', array('id'=>$regplanid)), 'additem', $electiveoptions);
        }


        return $out;
    }

    /**
     * Registrar plan elective item delete confirmation screen.
     * Refer to 3.5 of specification.
     *
     * @param integer $regplanid  registrar plan id
     * @param integer $itemid  registrar plan item id
     * @return string
     */
    public function training_plan_view_item_delete_confirmation($regplanid, $itemid) {
        global $DB;

        if ($itemname = $DB->get_field('local_evtp_regitem', 'name', array('id' => $itemid))) {
            $message = get_string('confirmdeleteregitem', 'local_evtp', $itemname);
            $continue = new moodle_url('/local/evtp/planview.php', array('id'  => $regplanid,
                                                                         'delitem' => $itemid,
                                                                         'confirm' => 1,
                                                                         'sesskey' => sesskey()));
            $cancel = new moodle_url('/local/evtp/planview.php', array('id' => $regplanid));

            return self::confirm($message, $continue, $cancel);
        }
        return false;
    }

    /**
     * Registrar training plan status editing screen.
     * Refer to 3.7 of specification.
     *
     * @param \local_evtp\regplan $regplan
     * @return string
     */
    public function training_plan_status(\local_evtp\regplan $regplan) {

        // Sanity check. This should have been caught earlier but good to check here as well.
        if (!$regplan->is_valid()) {
            redirect(new moodle_url('/local/evtp/plansearch.php'), get_string('invalidplanid', 'local_evtp'));
            exit;
        }

        // Set some variables.
        $regplanid = $regplan->get_id();
        $planviewurl = new moodle_url('/local/evtp/planview.php', array('id'=>$regplanid));
        $planstatusurl = new moodle_url('/local/evtp/planstatus.php', array('id'=>$regplanid));

        $out = self::heading(get_string('trainingplanstatus', 'local_evtp'));

        // Set up a table of registrar plan information.
        $table = new html_table();
        $table->head = array();
        $table->align = array('left', 'left');
        $table->id = 'registrarplanstatus';
        $table->data = array();

        $table->data[] = array(get_string('trainingplan', 'local_evtp'),
                               $regplan->get_name());

        $table->data[] = array(get_string('registrar', 'local_evtp'),
                               fullname($regplan->get_registrar()));

        $table->data[] = array(get_string('me', 'local_evtp'),
                               fullname($regplan->get_me()));

        $table->data[] = array(get_string('status', 'local_evtp'),
                               $regplan->get_status());

        $out .= html_writer::table($table);

        $out .= html_writer::empty_tag('hr');

        // Form for adding a new status log.
        $planstatusform = new \local_evtp\forms\planstatusform();

        $data = new \stdClass;
        $data->id = $regplanid;
        $data->status = $regplan->get_status_value();
        $planstatusform->set_data($data);
        
        if ($planstatusform->is_cancelled()) {
            redirect($planviewurl);
            exit;
        }

        if ($data = $planstatusform->get_data()) {
            if ($regplan->add_new_log($data)) {
                redirect($planviewurl, get_string('addednewplanstatus', 'local_evtp'));
                exit;
            } else {
                $out .= self::notify_problem(get_string('erroraddingnewplanstatus', 'local_evtp'));
            }
        }

        ob_start();
        $planstatusform->display();
        $out .= ob_get_contents();
        ob_end_clean();
 
        $out .= html_writer::empty_tag('hr');

        // Table of current status log entries.
        $logs = $regplan->get_logs_list();
        
        if (!empty($logs)) {
            $table = new html_table();
            $table->head = array(get_string('statusdate', 'local_evtp'),
                                 get_string('status', 'local_evtp'),
                                 get_string('who', 'local_evtp'),
                                 get_string('comments', 'local_evtp'),
                                 '');
            $table->align = array('left', 'left', 'left', 'left', 'center');
            $table->id = 'registrarplanstatuslogs';
            $table->data = array();

            foreach ($logs as $log) {
                $row = new html_table_row();

                // Delete icon and link.
                $delete = html_writer::link(
                    new moodle_url('/local/evtp/planstatus.php', array('id'     => $regplanid,
                                                                        'logid'  => $log->id,
                                                                        'action' => 'del')),
                    self::pix_icon('t/delete',
                        get_string('deleteitem', 'local_evtp'),
                        'moodle',
                        array('class' => 'iconsmall'))
                );

                $actionlinks = $delete;
                $row->cells = array($log->modifiedtime,
                                    $log->status,
                                    $log->who,
                                    $log->comment,
                                    $actionlinks);
                $table->data[] = $row;
            }
            $out .= html_writer::table($table);
        }
        return $out;
    }

    /**
     * Registrar plan status log deletion confirmation screen.
     * Refer to 3.7 of specification.
     *
     * @param object $regplan
     * @param integer $logid
     * @return string
     */
    public function training_plan_status_delete_confirmation($regplan, $logid) {
        global $DB;
    
        $regplanid = $regplan->get_id();
        if ($log = $regplan->get_log_by_id($logid)) {
            $entrydate = userdate($log->modifiedtime, get_string('strftimedate', 'langconfig'));
            $message = get_string('confirmdeleteplanstatuslog', 'local_evtp', $entrydate);
            $continue = new moodle_url('/local/evtp/planstatus.php', array('id'    => $regplanid,
                                                                            'logid' => $logid,
                                                                            'action'  => 'del',
                                                                            'confirm' => 1,
                                                                            'sesskey' => sesskey()));
            $cancel = new moodle_url('/local/evtp/planstatus.php', array('id' => $regplanid));

            return self::confirm($message, $continue, $cancel);
        }
        return false;
    }

    /**
     * Registrar training plan item edit.
     * Refer to 3.6 of specification.
     *
     * @param object $regplan
     * @param integer $itemid
     * @return string
     */
    public function training_plan_item($regplan, $itemid) {
        $regplanid = $regplan->get_id();
        $planviewurl = new moodle_url('/local/evtp/planview.php', array('id'=>$regplanid));

        $out = self::heading(get_string('trainingplanitem', 'local_evtp'));

        // Add a new registrar training plan item form
        $customdata = array('registrar' => (boolean)$regplan->is_registrar(),
                            'me'        => (boolean)$regplan->is_me(),
                            'admin'     => (boolean)$regplan->is_admin());
        $planitemform = new \local_evtp\forms\planitemform(null, $customdata);

        $item = $regplan->get_item($itemid);

        // Set up data to populate form.
        $data = new \stdClass;
        $data->id = $itemid;
        $data->regplanid = $regplanid;
        $data->planname = $regplan->get_name();
        $data->registrarname = fullname($regplan->get_registrar());
        $data->mename = fullname($regplan->get_me());
        $data->name = $item->name;
        $data->description = $item->description;
        $data->status = $item->status;
        if (!empty($item->startdate)) {
            $data->startdate = $item->startdate;
        }
        if (!empty($item->completiondate)) {
            $data->completiondate = $item->completiondate;
        }
        // Some fields may be static depending on who we are.
        if ($customdata['registrar']) {
            $data->plannedplacementeditor = array('text' => $item->plannedplacement);
            $data->registrarcommentseditor = array('text' => $item->registrarcomments);
        } else {
            $data->plannedplacement = $item->plannedplacement;
            $data->registrarcomments = $item->registrarcomments;
        }
        if ($customdata['me'] or $customdata['admin']) {
            $data->mecommentseditor = array('text' => $item->mecomments);
        } else {
            $data->mecomments = $item->mecomments;
        }

        $planitemform->set_data($data);

        if ($planitemform->is_cancelled()) {
            redirect($planviewurl);
            exit;
        }

        if ($data = $planitemform->get_data()) {
            $regplan->update_item($data);
            redirect($planviewurl, get_string('planitemupdated', 'local_evtp'));
            exit;
        }

        ob_start();
        $planitemform->display();
        $out .= ob_get_contents();
        ob_end_clean();

        return $out;
 
        

    }

}
