<?php
/**
 * EVTP Training Plans
 *
 * Training plan item  edit.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.
$action = optional_param('action', '', PARAM_ALPHA);
$cancel = optional_param('cancel', '', PARAM_ALPHA);
$itemid = optional_param('id', 0, PARAM_INT);
$planid = required_param('planid', PARAM_INT);

// Set up basic information.
$url     = new moodle_url('/local/evtp/itemedit.php', array('id' => $itemid, 'planid' => $planid));
$parenturl = new moodle_url('/local/evtp/templateplanedit.php', array('id' => $planid));
$context = context_system::instance();
$title   = get_string('trainingplanitemedit', 'local_evtp');

// Sanity checks.
require_login();
require_capability('local/evtp:manage', $context);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);
$PAGE->navbar->add(get_string('trainingplans', 'local_evtp'));
$PAGE->navbar->add(get_string('trainingplanlist', 'local_evtp'), new moodle_url('/local/evtp/planlist.php'));
$PAGE->navbar->add(get_string('trainingplanedit', 'local_evtp'), new moodle_url('/local/evtp/templateplanedit.php', array('id' => $planid)));
$PAGE->navbar->add($title);

$output = $PAGE->get_renderer('local_evtp');

// Redirect if form was cancelled.
if (!empty($cancel)) {
    redirect($parenturl);
    exit;
}

// Process submitted form.
if (($action == 'edit')) {
    $notifications = \local_evtp\utils::training_plan_item_edit();
    if (empty($notifications)) {
        redirect($parenturl, get_string('itemupdated', 'local_evtp'));
        exit;
    }
} else {
    $notifications = '';
}


echo $output->header();
echo $notifications;
echo $output->training_plan_item_edit($itemid, $planid);
echo $output->footer();
