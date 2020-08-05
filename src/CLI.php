<?php

namespace WP2StaticClodui;

use WP_CLI;


/**
 * WP2StaticClodui WP-CLI commands
 *
 * Registers WP-CLI commands for WP2StaticClodui under main wp2static cmd
 *
 */
class CLI {

    /**
     * Clodui commands
     *
     * @param string[] $args CLI args
     * @param string[] $assoc_args CLI args
     */
    public function clodui(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( empty( $action ) ) {
            WP_CLI::error( 'Missing required argument: <options>' );
        }

        if ( $action === 'options' ) {
            WP_CLI::line( 'TBC setting options for Clodui addon' );
        }
    }
}

