<?php
/**
 * EVTP Training Plans
 *
 * Registrar training plan view
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Get passed parameters.
$id = required_param('id', PARAM_INT);
$additem = optional_param('additem', 0, PARAM_INT);
$delitem = optional_param('delitem', 0, PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$review  = optional_param('review', false, PARAM_BOOL);

// Pre-setup checks.
$regplan = new \local_evtp\regplan($id);
if (!$regplan->is_valid()) {
    redirect(new moodle_url('/local/evtp/plansearch.php'), get_string('invalidplanid', 'local_evtp'));
    exit;
}

// Set up basic information.
$url     = new moodle_url('/local/evtp/planview.php', array('id' => $id));
$context = context_system::instance();
$title   = get_string('trainingplanview', 'local_evtp');

// Submit for review?
if ($review) {
    if ($regplan->submit_for_review()) {
        redirect($url, get_string('submittedforreview', 'local_evtp'));
        exit;
    } else {
        redirect($url);
        exit;
    }
}

// Add a new item to registrar plan.
if (!empty($additem)) {
    if ($regplan->add_new_item_to_plan($additem)) {
        $notification = get_string('itemaddedtoplan', 'local_evtp');
    } else {
        $notification = get_string('itemnotaddedtoplan', 'local_evtp');
    }
    redirect($url, $notification);
    exit;
}

// Delete an elective item.
if (!empty($delitem) and $confirm) {
    if ($regplan->delete_item_from_plan($delitem)) {
        $notification = get_string('itemremovedfromplan', 'local_evtp');
    } else {
        $notification = get_string('itemnotremovedfromplan', 'local_evtp');
    }
    redirect($url, $notification);
    exit;
}

// Sanity checks.
require_login();
require_capability('local/evtp:view', $context);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_cacheable(false);
$PAGE->navbar->add(get_string('trainingplans', 'local_evtp'));
$PAGE->navbar->add(get_string('trainingplansearch', 'local_evtp'), new moodle_url('/local/evtp/plansearch.php'));
$PAGE->navbar->add($title);


$output = $PAGE->get_renderer('local_evtp');

if (!empty($delitem)) {
    $out = $output->training_plan_view_item_delete_confirmation($id, $delitem);
} else {
    $out = $output->training_plan_view($regplan);
}

echo $output->header();
echo $out;
echo $output->footer();
