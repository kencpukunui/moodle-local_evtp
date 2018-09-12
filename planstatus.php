<?php
/**
 * EVTP Training Plans
 *
 * Registrar training plan status.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.
$id      = required_param('id', PARAM_INT);
$logid   = optional_param('logid', 0, PARAM_INT);
$action  = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);

// Pre-setup checks.
$regplan = new \local_evtp\regplan($id);
if (!$regplan->is_valid()) {
    redirect(new moodle_url('/local/evtp/plansearch.php'), get_string('invalidplanid', 'local_evtp'));
    exit;
}

// Set up basic information.
$url     = new moodle_url('/local/evtp/planstatus.php', array('id' => $id));
$context = context_system::instance();
$title   = get_string('trainingplanstatus', 'local_evtp');

// Sanity checks.
require_login();
require_capability('local/evtp:me', $context);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);
$PAGE->navbar->add(get_string('trainingplans', 'local_evtp'));
$PAGE->navbar->add(get_string('trainingplansearch', 'local_evtp'), new moodle_url('/local/evtp/plansearch.php'));
$PAGE->navbar->add(get_string('trainingplanview', 'local_evtp'), new moodle_url('/local/evtp/planview.php', array('id'=>$id)));
$PAGE->navbar->add($title);

$output = $PAGE->get_renderer('local_evtp');

// Any actions?
if (($action == 'del') and !empty($logid)) {
    if (empty($confirm)) {
        $out = $output->training_plan_status_delete_confirmation($regplan, $logid);
    } else if (confirm_sesskey()) {
        $regplan->remove_log($logid);
        redirect($url, get_string('statuslogremoved', 'local_evtp'));
        exit;
    }
} else {
    $out = $output->training_plan_status($regplan);
}

echo $output->header();
echo $out;
echo $output->footer();
