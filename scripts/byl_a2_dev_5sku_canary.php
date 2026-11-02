<?php
/**
 * BYL A2 — tightly bounded Dev-only Woo native stored-SKU Canary.
 * Non-CLI/web requests: NEVER. Default: DRY-RUN. All five preimages,
 * native product types/status/prices and three variations' parent/color
 * must match exactly before any write. No source WWW request/writes.
 *
 * A real run also needs a separate Owner exact-five approval, not merely
 * a backup assertion. To apply, the launch operator must explicitly
 * set BYL_A2_EXACT_FIVE_OWNER_PASS=pass_5sku_20261102 (no secrets).
 */
defined('ABSPATH') || exit;

function byl_a2_5sku_spec(): array {
    return [
        35550 => ['type'=>'simple','before'=>'VP757B','after'=>'VP757A',
                  'price'=>'129','sale'=>'89'],
        36134 => ['type'=>'simple','before'=>'VP757B & MF414A',
                  'after'=>'VP757A & MF414A','price'=>'185','sale'=>'148'],
        38164 => ['type'=>'variation','before'=>'','after'=>'pillow-blue',
                  'price'=>'59','sale'=>'','parent'=>38047,'color'=>'blue'],
        38165 => ['type'=>'variation','before'=>'','after'=>'pillow-green',
                  'price'=>'59','sale'=>'','parent'=>38047,'color'=>'green'],
        38166 => ['type'=>'variation','before'=>'','after'=>'pillow-grey',
                  'price'=>'59','sale'=>'','parent'=>38047,'color'=>'grey'],
    ];
}

function byl_a2_5sku_require(bool $ok, string $why): void {
    if (!$ok) { throw new RuntimeException('A2_FIVE_SKU_HOLD: ' . $why); }
}

function byl_a2_5sku_preflight(): array {
    byl_a2_5sku_require(PHP_SAPI === 'cli' && defined('WP_CLI') && WP_CLI,
        'WP-CLI only');
    byl_a2_5sku_require(wp_get_environment_type() === 'staging'
        && untrailingslashit((string) home_url()) === 'https://dev.beyourlover.com',
        'not verified Dev site');
    byl_a2_5sku_require(function_exists('byl_a1_dev_scheduler_should_pause')
        && byl_a1_dev_scheduler_should_pause()
        && class_exists('ActionScheduler')
        && (int) ActionScheduler::runner()->get_allowed_concurrent_batches() === 0,
        'Dev Action Scheduler not paused');
    byl_a2_5sku_require(function_exists('byl_a1_dev_jetpack_sync_hold_site')
        && byl_a1_dev_jetpack_sync_hold_site()
        && apply_filters('jetpack_sync_sender_should_load', true) === false
        && apply_filters('jetpack_sync_before_enqueue_updated_post_meta', [1,2,3]) === false,
        'Jetpack Sync not quarantined');
    byl_a2_5sku_require(function_exists('byl_a1_dev_product_rest_isolation_site')
        && byl_a1_dev_product_rest_isolation_site(),
        'Dev product REST not quarantined');
    byl_a2_5sku_require(has_filter('pre_wp_mail') !== false
        && has_filter('pre_http_request') !== false,
        'mail/HTTP guard absent');
    $parent = wc_get_product(38047);
    byl_a2_5sku_require($parent instanceof WC_Product
        && $parent->get_type() === 'variable'
        && $parent->get_sku('edit') === 'pillow'
        && $parent->get_status('edit') === 'publish',
        'PILLOW parent identity changed');

    $results = [];
    foreach (byl_a2_5sku_spec() as $id => $v) {
        $p = wc_get_product($id);
        byl_a2_5sku_require($p instanceof WC_Product, "missing ID {$id}");
        byl_a2_5sku_require($p->get_type() === $v['type'],
            "type mismatch ID {$id}");
        byl_a2_5sku_require($p->get_status('edit') === 'publish',
            "status mismatch ID {$id}");
        byl_a2_5sku_require($p->get_sku('edit') === $v['before']
            && (string) get_post_meta($id,'_sku',true) === $v['before'],
            "stored preimage mismatch ID {$id}");
        byl_a2_5sku_require($p->get_regular_price('edit') === $v['price']
            && $p->get_sale_price('edit') === $v['sale'],
            "price mismatch ID {$id}");
        if ($v['type'] === 'variation') {
            $attrs = $p->get_attributes('edit');
            byl_a2_5sku_require($p->get_parent_id() === $v['parent']
                && is_array($attrs) && count($attrs) === 1
                && (string) ($attrs['pa_color'] ?? '') === $v['color'],
                "variation parent/color mismatch ID {$id}");
        }
        $existing = wc_get_product_id_by_sku($v['after']);
        byl_a2_5sku_require(!$existing || (int) $existing === $id,
            "SKU collision ID {$id}");
        $results[$id] = $p;
    }
    return $results;
}

function byl_a2_5sku_run(): array {
    $products = byl_a2_5sku_preflight();
    $approved = getenv('BYL_A2_EXACT_FIVE_OWNER_PASS') === 'pass_5sku_20261102';
    $report = ['schema'=>'byl-a2-five-sku-canary-v1','site'=>home_url(),
        'source'=>'WWW_AUTHORITATIVE_20261102','expected_changes'=>5,
        'ready_preimages'=>count($products),
        'apply_requested'=>$approved,'changes'=>[], 'status'=>'DRY_RUN_ONLY'];
    if (!$approved) {
        foreach (byl_a2_5sku_spec() as $id=>$spec) {
            $report['changes'][]=['id'=>$id,'before'=>$spec['before'],
                'after'=>$spec['after'],'applied'=>false];
        }
        return $report;
    }
    // This file is off-webroot, does not contain keys/customer/order data,
    // and is created before the first WooCommerce product.save().
    $directory='/home/u247343654/byl-v3-staging/a2-sku-only-canary-20261102';
    if (!is_dir($directory)) {
        byl_a2_5sku_require(mkdir($directory,0700,true),
            'cannot create private preimage receipt directory');
    }
    $beforePath=$directory.'/exact-five-before.json';
    byl_a2_5sku_require(!file_exists($beforePath),
        'preimage receipt already exists: refuse repeat execution');
    $preimage=[];
    foreach (byl_a2_5sku_spec() as $id=>$spec) {
        $preimage[]=['id'=>$id,'before'=>$spec['before'],'target'=>$spec['after']];
    }
    byl_a2_5sku_require(file_put_contents($beforePath,
        wp_json_encode(['site'=>home_url(),'time_utc'=>gmdate('c'),
            'source'=>'WWW_OWNER_PASS','preimage'=>$preimage]),
        LOCK_EX)!==false,'preimage receipt write failed');
    chmod($beforePath,0600);

    $applied=[];
    try {
        foreach (byl_a2_5sku_spec() as $id=>$spec) {
            $product=$products[$id];
            $product->set_sku($spec['after']);
            $product->save();
            $actual=wc_get_product($id);
            byl_a2_5sku_require($actual instanceof WC_Product
                && $actual->get_sku('edit') === $spec['after']
                && (string)get_post_meta($id,'_sku',true)===$spec['after']
                && (int)wc_get_product_id_by_sku($spec['after'])===$id,
                'post-write stored SKU or lookup mismatch ID '.$id);
            $applied[]=$id;
            $report['changes'][]=['id'=>$id,'before'=>$spec['before'],
                'after'=>$spec['after'],'applied'=>true];
        }
        $report['status']='APPLIED_DEV_ONLY';
        file_put_contents($directory.'/exact-five-result.json',
            wp_json_encode($report),LOCK_EX);
        chmod($directory.'/exact-five-result.json',0600);
        return $report;
    } catch (Throwable $error) {
        $failed=false;
        foreach (array_reverse($applied) as $id) {
            try {
                $rollback=wc_get_product($id);
                if (!$rollback instanceof WC_Product) { $failed=true; continue; }
                $rollback->set_sku(byl_a2_5sku_spec()[$id]['before']);
                $rollback->save();
            } catch (Throwable $ignored) { $failed=true; }
        }
        // No claim to rollback any independent third-party effect.
        $report['status']=$failed?'ROLLBACK_UNCERTAIN':'ROLLBACK_ATTEMPTED';
        file_put_contents($directory.'/exact-five-failed.json',
            wp_json_encode(['status'=>$report['status'],
                'applied_before_failure'=>$applied]), LOCK_EX);
        chmod($directory.'/exact-five-failed.json',0600);
        throw new RuntimeException('A2_FIVE_SKU_ABORT: '.$report['status']);
    }
}

try { echo wp_json_encode(byl_a2_5sku_run()).PHP_EOL; }
catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(3);
}
