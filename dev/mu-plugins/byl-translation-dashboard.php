<?php
/**
 * BYL_TRANSLATION_DASHBOARD_V1 — read-only TranslatePress overview.
 * Deploy ONLY to dev.beyourlover.com/wp-content/mu-plugins/.
 * This file does not run translations, import QA, publish posts, or call paid APIs.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hard stop outside the one approved WordPress staging host.
if (function_exists('wp_get_environment_type') && wp_get_environment_type() !== 'staging') {
    return;
}
if (function_exists('home_url') && untrailingslashit(home_url()) !== 'https://dev.beyourlover.com') {
    return;
}

if (!defined('BYL_TRANSLATION_DASHBOARD_V1')) {
    define('BYL_TRANSLATION_DASHBOARD_V1', '1.0.0-dev');
}

if (!function_exists('byl_td_languages')) {
    function byl_td_languages() {
        $settings = get_option('trp_settings', array());
        if (!is_array($settings)) {
            $settings = array();
        }
        $source = isset($settings['default-language']) ? (string) $settings['default-language'] : 'en_US';
        $raw = isset($settings['translation-languages']) && is_array($settings['translation-languages'])
            ? $settings['translation-languages'] : array();
        $languages = array();
        foreach ($raw as $code) {
            $code = (string) $code;
            if ($code !== $source && preg_match('/^[a-zA-Z]{2,3}_[a-zA-Z]{2,3}$/', $code)) {
                $languages[$code] = $code;
            }
        }
        return array($source, array_values($languages));
    }
}

if (!function_exists('byl_td_table')) {
    function byl_td_table($source, $target) {
        global $wpdb;
        static $known = array();
        if (!preg_match('/^[a-zA-Z]{2,3}_[a-zA-Z]{2,3}$/', $source)
            || !preg_match('/^[a-zA-Z]{2,3}_[a-zA-Z]{2,3}$/', $target)) {
            return '';
        }
        $table = $wpdb->prefix . 'trp_dictionary_' . strtolower($source) . '_' . strtolower($target);
        if (array_key_exists($table, $known)) {
            return $known[$table];
        }
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        $known[$table] = $found === $table ? $table : '';
        return $known[$table];
    }
}

if (!function_exists('byl_td_candidates')) {
    function byl_td_candidates($post) {
        // Inspect only stored content: never execute dynamic shortcodes or change a page.
        $content = (string) $post->post_content;
        $values = array((string) $post->post_title);
        $segments = preg_split('/<\/(?:p|h[1-6]|li|td|th|div|blockquote)>|\R{2,}/i', $content);
        if (!is_array($segments)) {
            $segments = array();
        }
        foreach ($segments as $segment) {
            $value = html_entity_decode(wp_strip_all_tags((string) $segment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = trim(preg_replace('/\s+/u', ' ', $value));
            if (function_exists('mb_strlen') ? mb_strlen($value) >= 12 : strlen($value) >= 12) {
                $values[] = $value;
            }
            if (count($values) >= 160) {
                break;
            }
        }
        $values = array_values(array_unique(array_filter($values, 'strlen')));
        return array_slice($values, 0, 120);
    }
}

if (!function_exists('byl_td_inspect')) {
    function byl_td_inspect($post, $source, $target) {
        global $wpdb;
        $result = array('code' => 'unknown', 'label' => '未核验', 'sample' => 0, 'recognized' => 0,
            'translated' => 0, 'machine' => 0, 'reviewed' => 0, 'percent' => null,
            'note' => '翻译记录未与文章形成可信映射，不能据此判定 QA95。');
        $table = byl_td_table($source, $target);
        if ($table === '') {
            $result['code'] = 'missing';
            $result['label'] = '字典表未找到';
            return $result;
        }
        $strings = byl_td_candidates($post);
        $result['sample'] = count($strings);
        if (count($strings) < 3) {
            $result['label'] = '可识别内容不足';
            return $result;
        }
        $marks = implode(',', array_fill(0, count($strings), '%s'));
        // Table name is built exclusively from validated locale tokens and WP table prefix.
        $sql = "SELECT original, translated, status FROM `{$table}` WHERE original IN ({$marks})";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $strings), ARRAY_A);
        if (!is_array($rows)) {
            $result['label'] = '查询失败';
            return $result;
        }
        $result['recognized'] = count($rows);
        foreach ($rows as $row) {
            if (trim((string) $row['translated']) !== '' && (int) $row['status'] > 0) {
                ++$result['translated'];
                if ((int) $row['status'] === 1) {
                    ++$result['machine'];
                } elseif ((int) $row['status'] >= 2) {
                    ++$result['reviewed'];
                }
            }
        }
        if ($result['recognized'] === 0) {
            $result['code'] = 'unmapped';
            $result['label'] = '尚未匹配';
            $result['note'] = '未匹配到源文章字符串；可能未翻译，也可能由动态模块生成，不能等同于 0%。';
            return $result;
        }
        $result['percent'] = (int) round(100 * $result['translated'] / $result['recognized']);
        $result['code'] = $result['translated'] > 0 ? 'partial' : 'untranslated';
        $result['label'] = $result['translated'] > 0 ? '已发现翻译' : '匹配但未翻译';
        $result['note'] = '仅统计与文章静态文本精确匹配的 TranslatePress 字符串。不是全文完成率，也不是 QA95。';
        return $result;
    }
}

if (!function_exists('byl_td_qa')) {
    function byl_td_qa($post_id, $target) {
        // Reserved integration point for an audited Dev QA ledger; no implicit PASS.
        $evidence = apply_filters('byl_translation_dashboard_qa_evidence', null, (int) $post_id, (string) $target);
        if (!is_array($evidence)) {
            return array('label' => '尚无 Dev QA95 证据', 'pass' => false);
        }
        $score = isset($evidence['score']) && is_numeric($evidence['score']) ? (float) $evidence['score'] : null;
        $p0 = isset($evidence['p0_count']) && is_numeric($evidence['p0_count']) ? (int) $evidence['p0_count'] : null;
        $readback = !empty($evidence['dev_http_readback_200']);
        if ($score !== null && $score >= 95 && $p0 === 0 && $readback) {
            return array('label' => 'Dev QA95 PASS', 'pass' => true);
        }
        return array('label' => 'QA 未达验收条件', 'pass' => false);
    }
}

if (!function_exists('byl_td_frontend_url')) {
    /**
     * Use TranslatePress's own converter so localized SEO slugs, base paths,
     * and its installed language settings remain authoritative.
     */
    function byl_td_frontend_url($post, $language) {
        if (!is_object($post) || !isset($post->ID) || !class_exists('TRP_Translate_Press')) {
            return '';
        }
        list($source, $targets) = byl_td_languages();
        if ($language === $source || !in_array($language, $targets, true)) {
            return '';
        }
        $original = get_permalink($post);
        if (!is_string($original) || $original === '') {
            return '';
        }
        $instance = TRP_Translate_Press::get_trp_instance();
        $converter = is_object($instance) ? $instance->get_component('url_converter') : null;
        if (!is_object($converter) || !is_callable(array($converter, 'get_url_for_language'))) {
            return '';
        }
        // The empty third argument prevents a TranslatePress internal marker
        // from leaking into a browser-facing href.
        $translated = $converter->get_url_for_language($language, $original, '');
        if (!is_string($translated) || $translated === ''
            || wp_parse_url($translated, PHP_URL_HOST) !== 'dev.beyourlover.com') {
            return '';
        }
        return $translated;
    }
}

if (!function_exists('byl_td_query')) {
    function byl_td_query($export = false) {
        $type = isset($_GET['byl_type']) ? sanitize_key(wp_unslash($_GET['byl_type'])) : 'all';
        $state = isset($_GET['byl_state']) ? sanitize_key(wp_unslash($_GET['byl_state'])) : 'all';
        $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $types = in_array($type, array('post', 'page'), true) ? array($type) : array('post', 'page');
        $statuses = array('publish', 'draft', 'pending', 'private', 'future');
        if (in_array($state, $statuses, true)) {
            $statuses = array($state);
        }
        $raw_search = isset($_GET['byl_search']) ? sanitize_text_field(wp_unslash($_GET['byl_search'])) : '';
        $only_id = preg_match('/^#?\d+$/', trim($raw_search)) ? absint(ltrim(trim($raw_search), '#')) : 0;
        $args = array(
            'post_type' => $types,
            'post_status' => $statuses,
            'posts_per_page' => $export ? 1000 : 20,
            'paged' => $export ? 1 : $page,
            's' => $only_id ? '' : $raw_search,
            'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => $export,
        );
        if ($only_id) {
            $args['post__in'] = array($only_id);
        }
        return new WP_Query($args);
    }
}

if (!function_exists('byl_td_admin_page')) {
    function byl_td_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.'));
        }
        list($source, $languages) = byl_td_languages();
        $query = byl_td_query();
        $selected_lang = isset($_GET['byl_lang']) ? sanitize_text_field(wp_unslash($_GET['byl_lang'])) : 'all';
        $visible = array_filter($languages, static function ($code) use ($selected_lang) {
            return $selected_lang === 'all' || $selected_lang === $code;
        });
        $total = (int) $query->found_posts;
        $shown = count($query->posts);
        $target_count = count($languages);
        $csv_url = wp_nonce_url(add_query_arg(array_merge($_GET, array('action' => 'byl_td_csv')),
            admin_url('admin-post.php')), 'byl_td_csv');
        ?>
        <div class="wrap byl-td">
            <div class="byl-td-head"><div>
                <div class="byl-td-eyebrow">BYL / CONTENT OPERATIONS <span class="byl-td-version">V1 · DEV ONLY</span></div>
                <h1>翻译进度看板 <span class="byl-td-subtitle">Translation Dashboard</span></h1>
                <p>TranslatePress 原始数据只读观察 · 页面状态与 QA95 证据分离 · 不触发付费翻译</p>
            </div><a class="button button-primary" href="<?php echo esc_url($csv_url); ?>">下载 CSV</a></div>
            <div class="byl-td-cards">
                <div class="byl-td-card"><span>内容总数（当前筛选）</span><strong><?php echo esc_html(number_format_i18n($total)); ?></strong><small>文章 + 页面</small></div>
                <div class="byl-td-card"><span>目标语言</span><strong><?php echo esc_html($target_count); ?></strong><small>实时读取 TranslatePress 设置</small></div>
                <div class="byl-td-card"><span>理论翻译任务</span><strong><?php echo esc_html(number_format_i18n($total * $target_count)); ?></strong><small>不代表已翻译条目</small></div>
                <div class="byl-td-card"><span>当前页内容</span><strong><?php echo esc_html($shown); ?></strong><small>每页 20 条 · 只读</small></div>
            </div>
            <div class="notice notice-info inline byl-td-notice"><p><strong>数据口径：</strong>百分比仅为<strong>已匹配字符串的翻译占比</strong>，不是文章全文完成率；TranslatePress 的机器翻译标记也不等于 Dev QA95。只有具备来源分数、P0=0 和 Dev 页面 200 读回的 QA 账本才可显示 QA95 PASS。语言前台链接仅供查看，不代表已完成校对。</p></div>
            <form method="get" class="byl-td-filter">
                <input type="hidden" name="page" value="byl-translation-dashboard">
                <input type="search" name="byl_search" placeholder="搜索标题 / 关键词 / #文章ID" value="<?php echo esc_attr(isset($_GET['byl_search']) ? sanitize_text_field(wp_unslash($_GET['byl_search'])) : ''); ?>">
                <select name="byl_type"><option value="all">全部类型</option><?php foreach (array('post'=>'文章','page'=>'页面') as $v=>$label) { ?><option value="<?php echo esc_attr($v); ?>" <?php selected(isset($_GET['byl_type']) ? sanitize_key(wp_unslash($_GET['byl_type'])) : 'all', $v); ?>><?php echo esc_html($label); ?></option><?php } ?></select>
                <select name="byl_state"><option value="all">全部发布状态</option><?php foreach (array('publish'=>'已发布','draft'=>'草稿','pending'=>'待审','private'=>'私有','future'=>'定时') as $v=>$label) { ?><option value="<?php echo esc_attr($v); ?>" <?php selected(isset($_GET['byl_state']) ? sanitize_key(wp_unslash($_GET['byl_state'])) : 'all', $v); ?>><?php echo esc_html($label); ?></option><?php } ?></select>
                <select name="byl_lang"><option value="all">全部语言</option><?php foreach ($languages as $v) { ?><option value="<?php echo esc_attr($v); ?>" <?php selected($selected_lang, $v); ?>><?php echo esc_html($v); ?></option><?php } ?></select>
                <button class="button">筛选</button><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=byl-translation-dashboard')); ?>">重置</a>
            </form>
            <div class="byl-td-table-wrap"><table class="widefat striped byl-td-table"><thead><tr><th>文章 / 页面</th><th>英文原文</th><?php foreach ($visible as $code) { ?><th><?php echo esc_html($code); ?></th><?php } ?><th>状态</th><th>操作</th></tr></thead><tbody>
            <?php if (!$query->have_posts()) { ?><tr><td colspan="<?php echo esc_attr(4 + count($visible)); ?>">没有匹配的内容。</td></tr><?php } ?>
            <?php foreach ($query->posts as $post) { ?>
                <tr><td class="byl-td-title"><strong><?php echo esc_html(get_the_title($post)); ?></strong><small>#<?php echo esc_html($post->ID); ?> · <?php echo esc_html($post->post_type); ?> · <?php echo esc_html(get_the_date('Y-m-d', $post)); ?></small></td>
                    <td><span class="byl-td-pill green">EN · 源文章</span></td>
                    <?php foreach ($visible as $code) { $info = byl_td_inspect($post, $source, $code); $qa = byl_td_qa($post->ID, $code); $frontend_url = byl_td_frontend_url($post, $code); $language_names = array('es_ES'=>'西语','ko_KR'=>'韩语','ru_RU'=>'俄语'); $language_name = isset($language_names[$code]) ? $language_names[$code] : $code; ?>
                        <td><div class="byl-td-cell"><span class="byl-td-pill <?php echo esc_attr($info['code']); ?>"><?php echo esc_html($info['label']); ?></span>
                        <?php if ($info['percent'] !== null) { ?><div class="byl-td-meter"><span style="width:<?php echo esc_attr($info['percent']); ?>%"></span></div><small><?php echo esc_html($info['percent']); ?>% · <?php echo esc_html($info['translated']); ?>/<?php echo esc_html($info['recognized']); ?> 已匹配</small><?php } else { ?><small>进度未知 · 不做推断</small><?php } ?>
                        <small title="<?php echo esc_attr($info['note']); ?>"><?php echo esc_html($qa['label']); ?></small>
                        <?php if ($frontend_url !== '') { ?><a class="byl-td-frontend-link" href="<?php echo esc_url($frontend_url); ?>" target="_blank" rel="noopener noreferrer">查看<?php echo esc_html($language_name); ?>前台 ↗</a><?php } else { ?><small>语言前台链接不可用</small><?php } ?></div></td>
                    <?php } ?>
                    <td><span class="byl-td-pill muted"><?php echo esc_html($post->post_status); ?></span></td>
                    <td><a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">编辑原文</a> · <a href="<?php echo esc_url(get_permalink($post)); ?>" target="_blank" rel="noopener noreferrer">查看</a></td>
                </tr>
            <?php } ?>
            </tbody></table></div>
            <div class="byl-td-foot"><span>显示 <?php echo esc_html($shown); ?> / <?php echo esc_html($total); ?> 条。翻译字典按原字符串精确匹配，未命中不能认定为“未开始”。</span>
                <?php echo wp_kses_post(paginate_links(array('base'=>add_query_arg('paged','%#%'), 'format'=>'', 'current'=>max(1, absint(isset($_GET['paged']) ? $_GET['paged'] : 1)), 'total'=>max(1, (int) $query->max_num_pages), 'prev_text'=>'‹ 上一页', 'next_text'=>'下一页 ›'))); ?>
            </div>
        </div>
        <?php
    }
}

add_action('admin_menu', static function () {
    // Hidden native controller. Andy Core renders the visible Content L3 link;
    // this module must not introduce a second top-level WordPress menu.
    add_submenu_page(null, '翻译进度看板', '翻译看板', 'manage_options',
        'byl-translation-dashboard', 'byl_td_admin_page');
});

add_action('admin_post_byl_td_csv', static function () {
    if (!current_user_can('manage_options')) {
        wp_die('Forbidden', '', array('response' => 403));
    }
    check_admin_referer('byl_td_csv');
    list($source, $languages) = byl_td_languages();
    $query = byl_td_query(true);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="byl-translation-dashboard-dev-' . gmdate('Ymd-His') . '.csv"');
    $file = fopen('php://output', 'w');
    if (!$file) {
        exit;
    }
    fputs($file, "\xEF\xBB\xBF");
    $cols = array('Post ID', 'Type', 'Title', 'WP status');
    foreach ($languages as $code) {
        $cols[] = $code . ' observed state';
        $cols[] = $code . ' matched strings translated';
        $cols[] = $code . ' Dev QA95';
    }
    fputcsv($file, $cols);
    foreach ($query->posts as $post) {
        $row = array($post->ID, $post->post_type, get_the_title($post), $post->post_status);
        foreach ($languages as $code) {
            $info = byl_td_inspect($post, $source, $code);
            $qa = byl_td_qa($post->ID, $code);
            $row[] = $info['label'];
            $row[] = (string) $info['translated'] . '/' . (string) $info['recognized'] . ' (sample ' . (string) $info['sample'] . ')';
            $row[] = $qa['label'];
        }
        // Guard Excel against formula injection in a user-authored post title.
        foreach ($row as &$cell) {
            if (is_string($cell) && preg_match('/^[=+@\-\t\r]/', ltrim($cell))) {
                $cell = "'" . $cell;
            }
        }
        unset($cell);
        fputcsv($file, $row);
    }
    fclose($file);
    exit;
});

add_action('admin_head', static function () {
    if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'byl-translation-dashboard') {
        return;
    }
    ?>
    <style>
    .byl-td{max-width:1700px;margin:24px 28px 24px 8px;color:#233047}.byl-td-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:24px}.byl-td-head h1{font-size:29px;font-weight:750;line-height:1.3;margin:8px 0 5px}.byl-td-head p{color:#718096;font-size:13px;margin:0}.byl-td-eyebrow{font-size:11px;font-weight:800;letter-spacing:2px;color:#7156cb}.byl-td-version{display:inline-block;letter-spacing:0;border-radius:30px;padding:4px 9px;margin-left:7px;color:#7055c7;background:#eee8ff}.byl-td-subtitle{font-size:13px;font-weight:400;color:#8590a4;margin-left:8px}.byl-td-head .button-primary{background:#7057d2;border-color:#7057d2;border-radius:8px;padding:4px 18px}.byl-td-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:20px}.byl-td-card{background:#fff;border:1px solid #e7e9f1;border-radius:12px;padding:18px 20px;box-shadow:0 3px 14px rgba(39,47,78,.035)}.byl-td-card span{display:block;color:#8290a5;font-size:12px;margin-bottom:12px}.byl-td-card strong{display:block;font-size:29px;line-height:1.1;color:#28344c}.byl-td-card small{display:block;color:#a0a9b8;margin-top:10px;font-size:11px}.byl-td-notice{border-radius:8px;margin:0 0 18px!important}.byl-td-notice p{font-size:12px}.byl-td-filter{display:flex;align-items:center;gap:10px;margin:0 0 15px;flex-wrap:wrap}.byl-td-filter input[type=search]{width:270px}.byl-td-filter input,.byl-td-filter select{border-color:#dae0e8;border-radius:7px;min-height:35px}.byl-td-filter .button{border-radius:7px;min-height:35px}.byl-td-table-wrap{background:#fff;border:1px solid #e7e9f1;border-radius:12px;overflow:auto}.byl-td-table{border:0;box-shadow:none;border-collapse:collapse;table-layout:auto}.byl-td-table th{background:#f8f9fc;color:#718097;font-size:11px;font-weight:700;white-space:nowrap;padding:14px 16px}.byl-td-table td{padding:13px 16px;vertical-align:middle;border-top:1px solid #f0f1f5}.byl-td-table td:not(:first-child){min-width:135px}.byl-td-table .byl-td-title{min-width:260px;max-width:340px}.byl-td-title strong{display:block;font-size:12px;font-weight:650;line-height:1.45}.byl-td-title small,.byl-td-cell small{display:block;font-size:10px;color:#94a0b2;margin-top:7px}.byl-td-pill{display:inline-block;font-size:10px;font-weight:700;white-space:nowrap;border-radius:6px;padding:5px 9px;background:#f0eefb;color:#745fba}.byl-td-pill.green{background:#e8f8f0;color:#188758}.byl-td-pill.partial{background:#fff3d7;color:#ac7411}.byl-td-pill.missing,.byl-td-pill.untranslated{background:#fff0ef;color:#c4544f}.byl-td-pill.unmapped,.byl-td-pill.unknown,.byl-td-pill.muted{background:#f1f3f7;color:#79869c}.byl-td-meter{background:#f0f0f6;border-radius:9px;height:5px;width:108px;margin-top:9px;overflow:hidden}.byl-td-meter span{display:block;background:#8567dd;height:100%;border-radius:9px}.byl-td-cell{max-width:195px}.byl-td-frontend-link{display:inline-block;margin-top:9px;color:#6954c8;text-decoration:none;font-size:11px;font-weight:700}.byl-td-frontend-link:hover{text-decoration:underline}.byl-td-foot{display:flex;align-items:center;justify-content:space-between;gap:20px;font-size:12px;color:#8894a8;padding:16px 3px}.byl-td-foot .page-numbers{display:inline-block;text-decoration:none;background:#fff;border:1px solid #e3e6ee;border-radius:6px;padding:7px 11px;margin-left:4px}.byl-td-foot .page-numbers.current{color:#fff;background:#7459d0;border-color:#7459d0}@media(max-width:1050px){.byl-td-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.byl-td{margin-right:10px}.byl-td-cards{grid-template-columns:1fr}.byl-td-head{align-items:flex-start}.byl-td-subtitle{display:block;margin:3px 0}.byl-td-filter input[type=search]{width:100%}.byl-td-head h1{font-size:24px}}
    </style>
    <?php
});
