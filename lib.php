<?php
/**
 * EVTP Training Plans
 *
 * Core library functions.
 *
 * @package    local_evtp
 * @author     Shane Elliott (@link shane@pukunui.com)
 * @copyright  2018 Pukunui (@link https://pukunui.com/)
 * @license    https://www/gnu.org/copyleft/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the global navigation.
 *
 * @param global_navigation $nav
 */
function local_evtp_extend_navigation(global_navigation $nav) {
    $parentnode = navigation_node::create(
        get_string('trainingplans', 'local_evtp'),
        null,
        navigation_node::TYPE_CUSTOM,
        null,
        null,
        new pix_icon('i/folder', ''));

    if (has_capability('local/evtp:manage', context_system::instance())) {
        $listurl = new moodle_url('/local/evtp/templateplanlist.php');
        $listnode = navigation_node::create(
            get_string('templateplanlist', 'local_evtp'),
            $listurl,
            navigation_node::NODETYPE_LEAF,
            null,
            null,
            new pix_icon('i/settings', ''));
        $parentnode->add_node($listnode);
    }

    $searchurl = new moodle_url('/local/evtp/plansearch.php');
    $searchnode = navigation_node::create(
        get_string('trainingplansearch', 'local_evtp'),
        $searchurl,
        navigation_node::NODETYPE_LEAF,
        null,
        null,
        new pix_icon('i/settings', ''));
    $parentnode->add_node($searchnode);

    $nav->add_node($parentnode);
}
