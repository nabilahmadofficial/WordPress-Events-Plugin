// Register Custom Post Type
function create_events_post_type() {
    $args = array(
        'labels'      => array(
            'name'          => __('Events'),
            'singular_name' => __('Event')
        ),
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title'),
        'menu_icon'   => 'dashicons-calendar',
    );
    register_post_type('event', $args);
    remove_post_type_support('event', 'editor');
}
add_action('init', 'create_events_post_type');

// Add custom columns to event list
function add_event_custom_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key == 'title') {
            $new_columns[$key] = $value;
            $new_columns['event_date'] = 'Event Date';
            $new_columns['event_status'] = 'Status';
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}
add_filter('manage_event_posts_columns', 'add_event_custom_columns');

// Populate custom columns
function populate_event_custom_columns($column, $post_id) {
    switch ($column) {
        case 'event_date':
            $date = get_post_meta($post_id, '_event_date', true);
            echo !empty($date) ? date('F j, Y', strtotime($date)) : '—';
            break;
        case 'event_status':
            $date = get_post_meta($post_id, '_event_date', true);
            if (!empty($date)) {
                $event_date = strtotime($date);
                $current_date = current_time('timestamp');
                echo $event_date < $current_date ? '<span class="expired">EXPIRED</span>' : '<span class="active">ACTIVE</span>';
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_event_posts_custom_column', 'populate_event_custom_columns', 10, 2);

// Make the date column sortable
function event_sortable_columns($columns) {
    $columns['event_date'] = 'event_date';
    $columns['event_status'] = 'event_status';
    return $columns;
}
add_filter('manage_edit-event_sortable_columns', 'event_sortable_columns');

// Add sort logic for custom columns
function event_custom_orderby($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'event') {
        return;
    }
    if ($query->get('orderby') === 'event_date') {
        $query->set('meta_key', '_event_date');
        $query->set('orderby', 'meta_value');
    }
    if ($query->get('orderby') === 'event_status') {
        $today = date('Y-m-d');
        $query->set('meta_key', '_event_date');
        $query->set('orderby', 'meta_value');
        $meta_query = array('relation' => 'OR');
        $meta_query[] = array('key' => '_event_date', 'value' => $today, 'compare' => $query->get('order') === 'asc' ? '>=' : '<', 'type' => 'DATE');
        $meta_query[] = array('key' => '_event_date', 'value' => $today, 'compare' => $query->get('order') === 'asc' ? '<' : '>=', 'type' => 'DATE');
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'event_custom_orderby');

// Add Meta Boxes
function add_event_meta_boxes() {
    add_meta_box('event_details', 'Event Details', 'event_meta_callback', 'event', 'normal', 'high');
    add_meta_box('event_status', 'Event Status', 'event_status_callback', 'event', 'side', 'high');
}
add_action('add_meta_boxes', 'add_event_meta_boxes');

// Event Status Meta Box Callback
function event_status_callback($post) {
    $date = get_post_meta($post->ID, '_event_date', true);
    $status = 'Not Set';
    $status_class = 'status-neutral';
    if (!empty($date)) {
        $event_date = strtotime($date);
        $current_date = current_time('timestamp');
        if ($event_date < $current_date) {
            $status = 'EXPIRED';
            $status_class = 'status-expired';
        } else {
            $status = 'ACTIVE';
            $status_class = 'status-active';
        }
    }
    echo '<style>
        .event-status { padding: 8px; text-align: center; font-weight: bold; border-radius: 3px; }
        .status-active { background-color: #dff0d8; color: #3c763d; }
        .status-expired { background-color: #f2dede; color: #a94442; }
        .status-neutral { background-color: #fcf8e3; color: #8a6d3b; }
    </style>';
    echo '<div class="event-status ' . $status_class . '">' . $status . '</div>';
    if ($status == 'ACTIVE') {
        $days_left = ceil(($event_date - $current_date) / (60 * 60 * 24));
        echo '<p style="text-align: center; margin-top: 8px;">Days remaining: ' . $days_left . '</p>';
    }
}

// Enqueue admin scripts for datepicker and add custom styles
function event_admin_scripts() {
    global $post_type, $pagenow;
    if ('event' === $post_type || ('edit.php' === $pagenow && isset($_GET['post_type']) && 'event' === $_GET['post_type'])) {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        add_action('admin_head', 'event_admin_custom_styles');
    }
}
add_action('admin_enqueue_scripts', 'event_admin_scripts');

// Add custom styles for the event status in admin list view
function event_admin_custom_styles() {
    echo '<style>
        .column-event_status .active { background-color: #dff0d8; color: #3c763d; padding: 4px 8px; border-radius: 3px; font-weight: bold; }
        .column-event_status .expired { background-color: #f2dede; color: #a94442; padding: 4px 8px; border-radius: 3px; font-weight: bold; }
    </style>';
}

// Meta Box Callback
function event_meta_callback($post) {
    $address = get_post_meta($post->ID, '_event_address', true);
    $register_link = get_post_meta($post->ID, '_event_register_link', true);
    $more_info_link = get_post_meta($post->ID, '_event_more_info_link', true);
    $date = get_post_meta($post->ID, '_event_date', true);
    $time_from = get_post_meta($post->ID, '_event_time_from', true);
    $audience = get_post_meta($post->ID, '_event_audience', true);
    $audience_options = ['Industry', 'Students', 'Educators', 'Community'];
    ?>
    <label>Address:</label>
    <textarea name="event_address" class="widefat"><?php echo esc_textarea($address); ?></textarea>
    <label>Register Now Link:</label>
    <input type="url" name="event_register_link" value="<?php echo esc_attr($register_link); ?>" class="widefat" />
    <label>More Information Link:</label>
    <input type="url" name="event_more_info_link" value="<?php echo esc_attr($more_info_link); ?>" class="widefat" />
    <label>Date:</label>
    <input type="text" name="event_date" id="event_date" value="<?php echo esc_attr($date); ?>" class="widefat datepicker" />
    <script>
        jQuery(document).ready(function($) {
            $('.datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
    <label>Time From:</label>
    <input type="text" name Event Time From:</label>
    <input type="text" name="event_time_from" value="<?php echo esc_attr($time_from); ?>" class="widefat" />
    <div>
        <?php foreach ($audience_options as $option) { ?>
            <label>
                <input type="checkbox" name="event_audience[]" value="<?php echo esc_attr($option); ?>" <?php echo in_array($option, (array) $audience) ? 'checked' : ''; ?>>
                <?php echo esc_html($option); ?>
            </label><br>
        <?php } ?>
    </div>
    <?php
}

// Save Meta Fields
function save_event_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['event_address'], $_POST['event_register_link'], $_POST['event_more_info_link'], $_POST['event_date'], $_POST['event_time_from'])) {
        return;
    }
    update_post_meta($post_id, '_event_address', sanitize_textarea_field($_POST['event_address']));
    update_post_meta($post_id, '_event_register_link', esc_url($_POST['event_register_link']));
    update_post_meta($post_id, '_event_more_info_link', esc_url($_POST['event_more_info_link']));
    update_post_meta($post_id, '_event_date', sanitize_text_field($_POST['event_date']));
    update_post_meta($post_id, '_event_time_from', sanitize_text_field($_POST['event_time_from']));
    $audience = isset($_POST['event_audience']) ? array_map('sanitize_text_field', $_POST['event_audience']) : [];
    update_post_meta($post_id, '_event_audience', $audience);
}
add_action('save_post', 'save_event_meta');

// Enqueue front-end styles and scripts
function event_frontend_scripts() {
    wp_register_style('event-styles', false);
    wp_enqueue_style('event-styles');
    wp_add_inline_style('event-styles', '
        .events-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            justify-content: space-between;
        }
        .event-filter {
            margin-bottom: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .event-filter .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: #1A1A1A;
            margin-right: 15px;
        }
        .event-filter a {
            display: inline-flex;
            align-items: center;
            background: var(--color-white);
            padding: 8px 25px;
            line-height: 100%;
            text-transform: uppercase;
            border: 1px solid #f0ebea;
            color: #1A1A1A;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            transition: all 0.3s ease;
            border-radius: 4px;
            cursor: pointer;
        }
        .event-filter a:hover {
            background-color: #FF5500;
            color: #ffffff !important;
        }
        .event-filter a.active {
            background-color: #FF5500;
            color: #ffffff;
        }
        .widefat {
            border-spacing: 0;
            width: 100%;
            font-size: 15px;
            height: 40px;
            margin-top: 10px !important;
            margin-bottom: 5px !important;
            clear: both;
            margin: 0;
        }
        textarea.widefat { height: 100px; }
        .column {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 250px;
        }
        .event-info a.event-register-btn:hover { color: #ffffff !important; }
        .column-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-right: 0px !important;
            padding-left: 0px !important;
        }
        .event-content h3 {
            line-height: 32px;
            margin-bottom: 25px !important;
            font-weight: 600 !important;
            font-size: 21px !important;
        }
        .event-block {
            display: flex;
            flex-direction: column;
            height: 100%;
            width:100% !important;
            background: #fff;
            border: 1px solid #d5d5d5;
            padding: 30px;
            border-radius: 6px;
        }
        .event-header p {
            margin: 0;
            font-size: 17px;
            color: #4D4D4D;
        }
        .event-content { margin-top: 15px; }
        .event-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .keys {
            display: flex;
            gap: 7px;
        }
        .key {
            display: inline-block;
            background-color: #ffffff;
            color: #FF5500;
            border: 2px solid #FF5500;
            font-family: vox-round;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            text-align: center;
            line-height: 29px;
            font-weight: bold;
            font-size: 16px;
        }
        .event-header {
            border-bottom: 1px solid #d5d5d5;
            padding-bottom: 15px;
        }
        .event-location p {
            margin: 20px 0 0 0;
            font-size: 16px;
            color: #4D4D4D;
            white-space: pre-line !important;
            word-break: keep-all !important;
            overflow-wrap: break-word !important;
            hyphens: none !important;
        }
        .event-register-btn:hover { background-color: #FF5500; }
        .event-btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            height: 30px;
            width: 30px;
            border-radius: 0%;
            background-color: #FF5500;
            transition: all 0.3s ease;
        }
        .event-btn-icon svg {
            width: 12px;
            height: 12px;
            fill: #fff;
            transition: all 0.3s ease;
        }
        .event-register-btn:hover .event-btn-icon { background-color: #fff; }
        .event-register-btn:hover .event-btn-icon svg { fill: #FF5500; }
        a.event-register-btn {
            color: #1A1A1A;
            display: inline-flex;
            align-items: center;
            background: var(--color-white);
            padding: 8px 8px 8px 25px;
            clear: both;
            line-height: 100%;
            text-transform: uppercase;
            border: 1px solid #f0ebea;
            position: relative;
            z-index: 1;
            overflow: hidden;
            margin-right: 10px;
        }
        a.event-more-info-btn {
            color: #1A1A1A;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            font-size: 14px;
            margin-top:10px;
            font-weight: 500;
            position: relative;
            transition: color 0.3s ease;
        }
        a.event-more-info-btn:hover {
            color: #FF5500;
        }
        a.event-more-info-btn::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #FF5500;
            transition: width 0.3s ease;
        }
        a.event-more-info-btn:hover::after {
            width: 100%;
        }
        .event-more-info-btn .event-btn-icon {
            margin-left: 8px;
            width: 16px;
            height: 16px;
            background: none;
        }
        .event-more-info-btn .event-arrow-icon {
            width: 12px;
            height: 12px;
            fill: #1A1A1A;
            transform: rotate(-45deg);
            transition: fill 0.3s ease;
        }
        .event-more-info-btn:hover .event-arrow-icon {
            fill: #FF5500;
        }
        @media (max-width: 1024px) {
            .events-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .events-grid { grid-template-columns: 1fr; }
            .keys { gap: 4px; }
            .key {
                width: 30px;
                height: 30px;
                padding-top: 0px;
                font-weight: 700;
                font-size: 15px;
            }
            .event-info a.event-register-btn {
                padding: 10px 10px 10px 15px !important;
            }
            .event-info .event-btn-icon {
                margin-left: 10px;
                height: 32px;
                width: 32px;
            }
        }
        .event-tag {
            display: none;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .today-tag { background-color: #ff5722; color: white; }
        .tomorrow-tag { background-color: #ff9800; color: white; }
        .soon-tag { background-color: #4caf50; color: white; }
    ');

    wp_enqueue_script('jquery');
    wp_register_script('event-ajax', false);
    wp_enqueue_script('event-ajax');
    wp_add_inline_script('event-ajax', '
        jQuery(document).ready(function($) {
            let eventCache = { upcoming: {}, past: {} };

            function loadEvents(audience, isPast = false) {
                const cacheKey = isPast ? "past" : "upcoming";
                if (eventCache[cacheKey][audience]) {
                    $(".events-grid" + (isPast ? ".past-events" : "")).html(eventCache[cacheKey][audience]);
                    return;
                }

                $.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: {
                        action: "filter_events",
                        audience: audience,
                        is_past: isPast ? 1 : 0,
                        nonce: "' . wp_create_nonce('event_filter_nonce') . '"
                    },
                    success: function(response) {
                        eventCache[cacheKey][audience] = response;
                        $(".events-grid" + (isPast ? ".past-events" : "")).html(response);
                    },
                    error: function() {
                        $(".events-grid" + (isPast ? ".past-events" : "")).html("<p>Error loading events.</p>");
                    }
                });
            }

            $(".event-filter a").on("click", function(e) {
                e.preventDefault();
                const $this = $(this);
                $this.closest(".event-filter").find("a").removeClass("active");
                $this.addClass("active");
                const audience = $this.data("audience");
                const isPast = $this.closest(".past-events").length > 0;
                loadEvents(audience, isPast);
            });

            // Initial load for all audiences
            loadEvents("all");
            if ($(".past-events").length) {
                loadEvents("all", true);
            }
        });
    ');
}
add_action('wp_enqueue_scripts', 'event_frontend_scripts');

// AJAX handler for filtering events
function filter_events() {
    check_ajax_referer('event_filter_nonce', 'nonce');

    $audience = isset($_POST['audience']) ? sanitize_text_field($_POST['audience']) : 'all';
    $is_past = isset($_POST['is_past']) && $_POST['is_past'] == 1;
    $current_date = date('Y-m-d');

    $meta_query = [
        [
            'key' => '_event_date',
            'value' => $current_date,
            'compare' => $is_past ? '<' : '>=',
            'type' => 'DATE'
        ]
    ];

    if (!empty($audience) && $audience !== 'all') {
        $meta_query[] = [
            'key' => '_event_audience',
            'value' => $audience,
            'compare' => 'LIKE'
        ];
    }

    $query = new WP_Query([
        'post_type' => 'event',
        'posts_per_page' => $is_past ? -1 : (isset($_POST['limit']) ? intval($_POST['limit']) : -1),
        'meta_key' => '_event_date',
        'orderby' => 'meta_value',
        'order' => $is_past ? 'DESC' : 'ASC',
        'meta_query' => $meta_query
    ]);

    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $address = get_post_meta(get_the_ID(), '_event_address', true);
            $register_link = get_post_meta(get_the_ID(), '_event_register_link', true);
            $more_info_link = get_post_meta(get_the_ID(), '_event_more_info_link', true);
            $date = get_post_meta(get_the_ID(), '_event_date', true);
            $time_from = get_post_meta(get_the_ID(), '_event_time_from', true);
            $audience = get_post_meta(get_the_ID(), '_event_audience', true);
            $formatted_date = !empty($date) ? date('l, F j, Y', strtotime($date)) : 'Date TBD';
            $days_tag = '';
            if (!$is_past && !empty($date)) {
                $days_until = ceil((strtotime($date) - current_time('timestamp')) / (60 * 60 * 24));
                if ($days_until == 0) {
                    $days_tag = '<span class="event-tag today-tag">Today</span>';
                } elseif ($days_until == 1) {
                    $days_tag = '<span class="event-tag tomorrow-tag">Tomorrow</span>';
                } elseif ($days_until <= 7) {
                    $days_tag = '<span class="event-tag soon-tag">Coming Soon</span>';
                }
            }
            $audience_keys = is_array($audience) ? $audience : [];
            $audience_initials = '';
            foreach ($audience_keys as $aud) {
                $initial = strtoupper(substr(trim($aud), 0, 1));
                $audience_initials .= '<span class="key">' . $initial . '</span>';
            }
            $register_button = !$is_past && !empty($register_link) ? '<a href="' . esc_url($register_link) . '" target="_blank" rel="nofollow" class="event-register-btn"><span>Register Now</span><div class="event-btn-icon"><svg class="event-arrow-icon" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg></div></a>' : '';
            $more_info_button = !empty($more_info_link) ? '<a href="' . esc_url($more_info_link) . '" rel="nofollow" class="event-more-info-btn"><span>More Information</span><div class="event-btn-icon"><svg class="event-arrow-icon" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg></div></a>' : '';
            ?>
            <div class="column column-block">
                <div class="event-block<?php echo $is_past ? ' past-event' : ''; ?>">
                    <div class="event-header">
                        <p><?php echo esc_html($formatted_date); ?></p>
                        <p><?php echo esc_html($time_from); ?></p>
                        <?php echo $days_tag; ?>
                    </div>
                    <div class="event-content">
                        <h3><?php the_title(); ?></h3>
                        <div class="event-info">
                            <div class="keys"><?php echo $audience_initials; ?></div>
                            <?php echo $register_button; ?>
                        </div>
                        <div class="event-location">
                            <p><?php echo wp_kses_post($address); ?></p>
                            <?php echo $more_info_button; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p>No ' . ($is_past ? 'past' : 'upcoming') . ' events.</p>';
    }

    $output = ob_get_clean();
    echo $output;
    wp_die();
}
add_action('wp_ajax_filter_events', 'filter_events');
add_action('wp_ajax_nopriv_filter_events', 'filter_events');

// Display Events on Frontend
function display_events_shortcode($atts = []) {
    $attributes = shortcode_atts(['limit' => -1], $atts);
    $current_date = date('Y-m-d');
    $audience_options = ['all' => 'All', 'Industry' => 'Industry', 'Students' => 'Students', 'Educators' => 'Educators', 'Community' => 'Community'];

    $query = new WP_Query([
        'post_type' => 'event',
        'posts_per_page' => intval($attributes['limit']),
        'meta_key' => '_event_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => '_event_date',
                'value' => $current_date,
                'compare' => '>=',
                'type' => 'DATE'
            ]
        ]
    ]);

    ob_start();
    ?>
    <div class="event-filter">
        <?php foreach ($audience_options as $value => $label) : ?>
            <a href="#" data-audience="<?php echo esc_attr($value); ?>" class="<?php echo $value === 'all' ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="events-grid">
        <?php
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $address = get_post_meta(get_the_ID(), '_event_address', true);
                $register_link = get_post_meta(get_the_ID(), '_event_register_link', true);
                $more_info_link = get_post_meta(get_the_ID(), '_event_more_info_link', true);
                $date = get_post_meta(get_the_ID(), '_event_date', true);
                $time_from = get_post_meta(get_the_ID(), '_event_time_from', true);
                $audience = get_post_meta(get_the_ID(), '_event_audience', true);
                $formatted_date = !empty($date) ? date('l, F j, Y', strtotime($date)) : 'Date TBD';
                $days_tag = '';
                if (!empty($date)) {
                    $days_until = ceil((strtotime($date) - current_time('timestamp')) / (60 * 60 * 24));
                    if ($days_until == 0) {
                        $days_tag = '<span class="event-tag today-tag">Today</span>';
                    } elseif ($days_until == 1) {
                        $days_tag = '<span class="event-tag tomorrow-tag">Tomorrow</span>';
                    } elseif ($days_until <= 7) {
                        $days_tag = '<span class="event-tag soon-tag">Coming Soon</span>';
                    }
                }
                $audience_keys = is_array($audience) ? $audience : [];
                $audience_initials = '';
                foreach ($audience_keys as $aud) {
                    $initial = strtoupper(substr(trim($aud), 0, 1));
                    $audience_initials .= '<span class="key">' . $initial . '</span>';
                }
                $register_button = !empty($register_link) ? '<a href="' . esc_url($register_link) . '" target="_blank" rel="nofollow" class="event-register-btn"><span>Register Now</span><div class="event-btn-icon"><svg class="event-arrow-icon" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg></div></a>' : '';
                $more_info_button = !empty($more_info_link) ? '<a href="' . esc_url($more_info_link) . '" rel="nofollow" class="event-more-info-btn"><span>More Information</span><div class="event-btn-icon"><svg class="event-arrow-icon" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg></div></a>' : '';
                ?>
                <div class="column column-block">
                    <div class="event-block">
                        <div class="event-header">
                            <p><?php echo esc_html($formatted_date); ?></p>
                            <p><?php echo esc_html($time_from); ?></p>
                            <?php echo $days_tag; ?>
                        </div>
                        <div class="event-content">
                            <h3><?php the_title(); ?></h3>
                            <div class="event-info">
                                <div class="keys"><?php echo $audience_initials; ?></div>
                                <?php echo $register_button; ?>
                            </div>
                            <div class="event-location">
                                <p><?php echo wp_kses_post($address); ?></p>
                                <?php echo $more_info_button; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            wp_reset_postdata();
        } else {
            echo '<p>No upcoming events.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('display_events', 'display_events_shortcode');

// Display Past Events on Frontend
function display_past_events_shortcode() {
    $current_date = date('Y-m-d');
    $audience_options = ['all' => 'All', 'Industry' => 'Industry', 'Students' => 'Students', 'Educators' => 'Educators', 'Community' => 'Community'];

    $query = new WP_Query([
        'post_type' => 'event',
        'posts_per_page' => -1,
        'meta_key' => '_event_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => '_event_date',
                'value' => $current_date,
                'compare' => '<',
                'type' => 'DATE'
            ]
        ]
    ]);

    ob_start();
    ?>
    <h2>Past Events</h2>
    <div class="event-filter">
        <?php foreach ($audience_options as $value => $label) : ?>
            <a href="#" data-audience="<?php echo esc_attr($value); ?>" class="<?php echo $value === 'all' ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="events-grid past-events">
        <?php
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $address = get_post_meta(get_the_ID(), '_event_address', true);
                $more_info_link = get_post_meta(get_the_ID(), '_event_more_info_link', true);
                $date = get_post_meta(get_the_ID(), '_event_date', true);
                $time_from = get_post_meta(get_the_ID(), '_event_time_from', true);
                $audience = get_post_meta(get_the_ID(), '_event_audience', true);
                $formatted_date = !empty($date) ? date('l, F j, Y', strtotime($date)) : 'Date TBD';
                $audience_keys = is_array($audience) ? $audience : [];
                $audience_initials = '';
                foreach ($audience_keys as $aud) {
                    $initial = strtoupper(substr(trim($aud), 0, 1));
                    $audience_initials .= '<span class="key">' . $initial . '</span>';
                }
                $more_info_button = !empty($more_info_link) ? '<a href="' . esc_url($more_info_link) . '" rel="nofollow" class="event-more-info-btn"><span>More Information</span><div class="event-btn-icon"><svg class="event-arrow-icon" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg></div></a>' : '';
                ?>
                <div class="column column-block">
                    <div class="event-block past-event">
                        <div class="event-header">
                            <p><?php echo esc_html($formatted_date); ?></p>
                            <p><?php echo esc_html($time_from); ?></p>
                        </div>
                        <div class="event-content">
                            <h3><?php the_title(); ?></h3>
                            <div class="event-info">
                                <div class="keys"><?php echo $audience_initials; ?></div>
                            </div>
                            <div class="event-location">
                                <p><?php echo wp_kses_post($address); ?></p>
                                <?php echo $more_info_button; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            wp_reset_postdata();
        } else {
            echo '<p>No past events.</p>';
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('display_past_events', 'display_past_events_shortcode');