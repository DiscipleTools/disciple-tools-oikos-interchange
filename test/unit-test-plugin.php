<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'dt-oikos-system/dt-oikos-system.php' );

        $this->assertContains(
            'dt-oikos-system/dt-oikos-system.php',
            get_option( 'active_plugins' )
        );
    }
}
