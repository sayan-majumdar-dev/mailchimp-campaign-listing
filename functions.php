//Create a shortcode to display mailchimp campaigns

function display_mailchimp_campaigns() {
    $api_key = '{your_mailchimp_api_key}';
    $dc = '{your_mailcjimp_data_center}';
    $url = "https://$dc.api.mailchimp.com/3.0/campaigns?count=1000";

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode('anystring:' . $api_key),
        ]
    ]);

    if (is_wp_error($response)) {
        return 'Failed to fetch campaigns.';
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['campaigns'])) {
        return 'No campaigns found.';
    }

    $items = [];
    $skipped = [];

    foreach ($body['campaigns'] as $campaign) {
        $title = $campaign['settings']['title'];
        $url = $campaign['long_archive_url'];

        if (preg_match('/(Issue|Edition)\s+(\d+)/i', $title, $matches)) {
            $issue_num = intval($matches[2]);
            $items[] = [
                'issue' => $issue_num,
                'title' => $title,
                'url' => $url
            ];
        } else {
            $skipped[] = $title;
        }
    }

    // Sort by issue number
    usort($items, function ($a, $b) {
        return $a['issue'] <=> $b['issue'];
    });

    // Render output
    $output = '<ul>';
    foreach ($items as $item) {
        $output .= '<li><a href="' . esc_url($item['url']) . '" target="_blank">' . esc_html($item['title']) . '</a></li>';
    }
    $output .= '</ul>';

    // Optional: Debug skipped items (only shown to admins)
    if (current_user_can('administrator') && !empty($skipped)) {
        $output .= '<h4>⚠️ Skipped Titles (Check formatting):</h4><ul>';
        foreach ($skipped as $s) {
            $output .= '<li>' . esc_html($s) . '</li>';
        }
        $output .= '</ul>';
    }

    return $output;
}
add_shortcode('mailchimp_campaigns', 'display_mailchimp_campaigns');
