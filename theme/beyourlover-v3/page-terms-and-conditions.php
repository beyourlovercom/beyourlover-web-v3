<?php
/**
 * Dev Terms-only default slug template.
 *
 * Routes exactly /terms-and-conditions/ through BYL Simple Page without
 * changing _wp_page_template metadata or other Pages/Posts. WordPress's
 * normal template hierarchy gives explicit, valid templates precedence.
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
require get_stylesheet_directory().'/template-simple-page.php';
