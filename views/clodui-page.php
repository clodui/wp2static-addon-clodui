<h2>Clodui Deployment Options</h2>

<h3>Clodui</h3>

<form
    name="wp2static-clodui-save-options"
    method="POST"
    action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">

    <?php wp_nonce_field( $view['nonce_action'] ); ?>
    <input name="action" type="hidden" value="wp2static_clodui_save_options" />

<table class="widefat striped">
    <tbody>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['websiteID']->name; ?>"
                ><?php echo $view['options']['websiteID']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['websiteID']->name; ?>"
                    name="<?php echo $view['options']['websiteID']->name; ?>"
                    type="text"
                    style="min-width: 50%"
                    placeholder="Website id"
                    value="<?php echo $view['options']['websiteID']->value !== '' ? $view['options']['websiteID']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['username']->name; ?>"
                ><?php echo $view['options']['username']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['username']->name; ?>"
                    name="<?php echo $view['options']['username']->name; ?>"
                    type="email"
                    placeholder="Clodui username"
                    style="min-width: 50%"
                    value="<?php echo $view['options']['username']->value !== '' ? $view['options']['username']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['tokenID']->name; ?>"
                ><?php echo $view['options']['tokenID']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['tokenID']->name; ?>"
                    name="<?php echo $view['options']['tokenID']->name; ?>"
                    type="text"
                    placeholder="Clodui token id"
                    style="min-width: 50%"
                    value="<?php echo $view['options']['tokenID']->value !== '' ? $view['options']['tokenID']->value : ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['token']->name; ?>"
                ><?php echo $view['options']['token']->label; ?></label>
            </td>
            <td>
                <input
                    id="<?php echo $view['options']['token']->name; ?>"
                    name="<?php echo $view['options']['token']->name; ?>"
                    type="password"
                    placeholder="Clodui token"
                    style="min-width: 50%"
                    value="<?php echo $view['options']['token']->value !== '' ?
                        \WP2Static\CoreOptions::encrypt_decrypt('decrypt', $view['options']['token']->value) :
                        ''; ?>"
                />
            </td>
        </tr>

        <tr>
            <td style="width:50%;">
                <label
                    for="<?php echo $view['options']['logLevel']->name; ?>"
                ><?php echo $view['options']['logLevel']->label; ?></label>
            </td>
            <td>
                <select
                    id="<?php echo $view['options']['logLevel']->name; ?>"
                    name="<?php echo $view['options']['logLevel']->name; ?>"
                >
                    <?php 
                        $opts = array(
                            'ERROR',
                            'WARN',
                            'INFO',
                            'DEBUG'
                        );
                        foreach($opts as $opt) {
                            $selected = $view['options']['logLevel']->value == $opt ? 'selected' : '';
                            echo '<option value="'. $opt . '" '. $selected .'>'. $opt .'</option>';
                        }
                    ?>
                </select>
            </td>
        </tr>

    </tbody>
</table>

<br>

    <button class="button btn-primary">Save Clodui Options</button>
</form>

