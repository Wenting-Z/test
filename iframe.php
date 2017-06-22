<?php
/**
 * Plugin Name: iframe whitelist
 */

function settings_iframe_init() {
    // register a new setting for "settings_iframe" page
    register_setting( 'settings_iframe', 'settings_iframe_options' );
    // register a new section in the "settings_iframe" page
    add_settings_section( // see https://codex.wordpress.org/Function_Reference/add_settings_section for more info
        'settings_iframe_section_id', // id of the Section
        'Description', // Name of the section
        'settings_iframe_section_cb', // Function that will render the section
        'settings_iframe' // The id of the page that this section will be on
    );
    // reguster a field to the section we just made
    add_settings_field( // see https://codex.wordpress.org/Function_Reference/add_settings_field for more info
        'settings_iframe_field_id', // field id
        "Iframe URL", // field name
        'settings_iframe_field_cb', // function to render field
        'settings_iframe', // page id the field will be on
        'settings_iframe_section_id', // section id the field will appear in
        array( // this is an array of arguments to pass to the callback function that will render the field
            'label_for' => 'settings_iframe_field',
            'class' => 'settings_iframe_row',
       )
    );
}
/**
 * register our settings_iframe_init to the admin_init action hook
 */
add_action( 'admin_init', 'settings_iframe_init' );

/**
 * custom option and settings:
 * callback functions
 */
// developers section cb
// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function settings_iframe_section_cb( $args ) { // This is the function that was named in add_settings_section above to render the section, it can contain any php or html, but usually just a description
    $options = get_option( 'settings_iframe_options' ); // This grabs the existing options for this page and assigns them to a variable for use, they aren't used in this function though
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); //This just pulls one of the default arguments, in this case id, and echos it into the id for styling with css  ?>">The iframe domains listed below are allowed to be added. Enters one domain in one line</p>
    <?php
}
function settings_iframe_field_cb( $args ) { // This is the function that was named in add_settings_field above to render the section, this is where you will do most of the actual adding of the html that the user interacts with
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'settings_iframe_options' ); //Again load the exting values to put in to the textfield
    
    // output the field, in this case just a textarea field, it could be input for text, checkbox, radio button, etc
    ?>
        <textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" name="settings_iframe_options[<?php echo esc_attr( $args['label_for'] ); ?>]" rows="7" cols="50" type="textarea"><?php echo $options['settings_iframe_field']; ?></textarea>

    <?php
//     var_dump($whitelist);
}
function settings_iframe_options_page() { // This is what actually adds the page to the WordPress backend
    add_submenu_page( // see https://developer.wordpress.org/reference/functions/add_submenu_page/, you could also add it as a top level with add_menu_page, but it is bad to crowd the top level menu space with options pages
        'options-general.php', // the php file for the parent template, or ID if it is not a default page
        'Settings IFramelist', //Page title as it appears at the top of the page
        'Settings IFramelist', // Page title as it appears in the menu
        'manage_options', // WordPress capability required to access the page
        'settings_iframe', // the id of the page, should match the id of the page you added to the add_settings_section function earlier
        'settings_iframe_options_page_html' // callback function that renders the page
    );
}
add_action( 'admin_menu', 'settings_iframe_options_page' ); // Hook that triggers the above function.
function settings_iframe_options_page_html() {
    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) { //Double check user capabilities to make sure they should be editing this page
        return; // If they don't have the right capabilities just return a blank page
    }
    // add error/update messages
    // check if the user have submitted the settings
    // wordpress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {
    // add settings saved message with the class of "updated"
        add_settings_error( 'settings_iframe_messages', 'settings_iframe_message', __( 'Settings Saved', 'settings_iframe' ), 'updated' ); // This adds a success message if the user has just updated the settings
    }
    // show error/update messages
    settings_errors( 'settings_iframe_messages' );
    ?>
    <!-- You could add css styling in the style tags below if you wanted to -->
    <style>
    </style>
    <div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
    <?php
    // WordPress will do most of the work for us in outputting the form
    // output security fields for the registered setting "settings_iframe"
    settings_fields( 'settings_iframe' );
    // output setting sections and their fields
    // (sections are registered for "settings_iframe", each field is registered to a specific section)
    do_settings_sections( 'settings_iframe' );
    // output save settings button
    submit_button( 'Save Settings' );
    ?>
    </form>
    </div>

    <?php
}

/**
 * returns the iframe whitelist set by user
 */
function get_whitelist(){
    $options = get_option( 'settings_iframe_options' );
    $array = explode("\n", $options["settings_iframe_field"]);
    foreach($array as $index => $word){
        if(strpos($word, "http") !== false){
            $word = parse_url($word)["host"];
        }
        if(substr($word, -1) === '_'){
            $word = substr_replace($word, "", -1); //or $word = substr($word, 0, -1);
        }
        $array[$index] = trim($word);
    }
    return $array;
}

/**
 * compares the given url to the whitelist. generates the iframe if the url is allowed
 */
function iframe_whitelist($atts){
    $a = shortcode_atts( array(
        'width' => "500",
        'height' => "200",
    ), $atts );
    
    $whitelist = get_whitelist();
    $output = "Iframe URL is not allowed";
//     return var_dump($whitelist);
   
    for($x = 0; $x < count($whitelist); $x++) {
        if(strpos($atts['src'], $whitelist[$x]) !== false){
            $output = "<iframe src=\"{$atts['src']}\" width={$a['width']} height={$a['height']}></iframe>";
            break;
        }
    }
   return $output;
}
add_shortcode('iframe', 'iframe_whitelist');