<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
/**
 * Temporary Dev-only V5 art-direction implementation. Owner explicitly authorized
 * previewing the rejected V5 design on Dev; PR #109 remains Visual FAIL.
 * No inline screenshot snapshots of WWW. Existing Dev lower PHP preserved below.
 * Once actual WWW lower modules are ported faithfully, remove temporary legacy tail.
 */
get_header();
$popular_products = byl_v3_home_products(4);
$byl_v5_product_link = static function (string $slug): string {
    $post = get_page_by_path($slug, OBJECT, 'product');
    return $post instanceof WP_Post ? get_permalink($post) : home_url('/new-arrivals/');
};
$byl_v5_category_link = static function (string $slug): string {
    $term = get_term_by('slug', $slug, 'product_cat');
    if ($term instanceof WP_Term) {
        $link = get_term_link($term);
        if (!is_wp_error($link)) { return (string) $link; }
    }
    return home_url('/shop/');
};
?>
<style id="byl-home-v5-temp">

.byl-v5-page{--ink:#322c2b;--ox:#763841;--oxdeep:#552833;--rose:#b97780;--muted:#746964;--ivory:#fbfaf7;--warm:#f2eae3;--line:#d8cfca;--sage:#abb5a6}
.byl-v5-page,.byl-v5-page *{box-sizing:border-box}main.byl-v5-page{margin:0;background:#fff;color:var(--ink);font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}.byl-v5-page h1,.byl-v5-page h2,.byl-v5-page h3,.byl-v5-page p{margin-top:0}.byl-v5-page h1,.byl-v5-page h2,.byl-v5-page h3,.byl-v5-page .serif{font-family:Georgia,"Times New Roman",serif;font-weight:400}
.original{position:relative;overflow:hidden;width:100%;background:white}
.original img{display:block;position:absolute;left:0;top:0;max-width:none;width:1440px}
.original img.mob{display:none}.orig-header{height:194px}
.unchanged{height:8298px}.unchanged>img.desk{top:-2260px}
.byl-v5-page button,.byl-v5-page a{font-family:inherit}
.v4{background:var(--ivory)}
.container{max-width:1370px;margin:auto;padding:0 48px}
.hero{height:725px;position:relative;overflow:hidden;background:#f7f3ed}
.hero .hero-copy{position:absolute;z-index:2;top:152px;left:max(64px,calc((100vw - 1270px)/2));max-width:600px}
.eyebrow{font-size:11px;letter-spacing:3.5px;font-weight:700;color:#95606b;line-height:1.35}
.hero .hero-copy .eyebrow{letter-spacing:3px}
.hero h1{font-size:108px;line-height:.94;letter-spacing:-6px;margin:29px 0 25px;color:#503138}
.hero h1 em{font-weight:400;color:#a96e77}
.hero-copy p{font-size:15px;line-height:1.9;color:#655d59;max-width:420px;margin-bottom:28px}
.cta{display:inline-flex;align-items:center;justify-content:space-between;gap:42px;padding:17px 25px;background:var(--oxdeep);color:#fff;font-size:12px;letter-spacing:.4px;text-decoration:none;min-width:244px}
.hero-smallprint{position:absolute;bottom:45px;left:max(64px,calc((100vw - 1270px)/2));font-size:10px;letter-spacing:1.8px;color:#967a78;z-index:3}
.hero-art{position:absolute;right:0;top:-4px;height:100%;width:57%;display:flex;align-items:center;justify-content:center}
.hero-art svg{height:100%;max-height:730px;width:100%;overflow:visible}
.hero .thin{position:absolute;top:0;left:50%;height:100%;width:1px;background:linear-gradient(transparent,#d1c0bd55,transparent)}
.quick{background:#fefdfa;border-bottom:1px solid #e9e3df}
.quick .container{padding-top:26px;padding-bottom:29px}
.quick-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;border-bottom:1px solid #dfd6d1;padding-bottom:13px;margin-bottom:7px}
.quick-heading h2{font-size:24px;line-height:1.2;letter-spacing:-.5px;margin:0;color:#463538}
.quick-heading .small{font-size:10px;color:#968782;letter-spacing:2.2px}
.quick-list{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));align-items:start;gap:15px;padding-top:8px}
.quick-item{color:#514148;text-decoration:none;display:flex;flex-direction:column;align-items:center;gap:12px;position:relative;min-width:0;justify-content:flex-end;padding:13px 0 4px;height:153px}
.quick-item .art{width:118px;height:109px;display:flex;align-items:center;justify-content:center;transition:transform .2s ease}
.quick-item .art img{width:100%;height:100%;object-fit:contain;mix-blend-mode:multiply}
.quick-item:hover .art,.quick-item:focus-visible .art{transform:translateY(-5px) scale(1.04)}
.quick-item .name{font-size:10px;letter-spacing:1.7px;font-weight:700;text-align:center}
.quick-item:after{content:"";display:block;position:absolute;bottom:-9px;left:50%;width:0;height:1px;background:#8e5160;transition:width .25s,left .25s}
.quick-item:hover:after,.quick-item:focus-visible:after{left:25%;width:50%}
.quick-swipe{display:none}
.manifesto{background:#fff;position:relative;overflow:hidden}
.manifesto .container{display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:4%;padding-top:103px;padding-bottom:100px}
.manifesto-art{height:420px;position:relative;border-left:1px solid #dec9c4;background:radial-gradient(circle at 60% 45%,#f3ded9 2%,#f9f6f2 68%)}
.manifesto-art svg{width:100%;height:100%}
.manifesto-copy{padding-right:60px}
.manifesto-copy h2{font-size:66px;line-height:1.08;letter-spacing:-2.5px;margin:20px 0 22px;color:#573c3d}
.manifesto-copy h2 i{font-family:Georgia,serif;color:#a46e79}
.manifesto-copy p{font-size:14px;line-height:1.8;color:#746964;max-width:400px}
.manifesto-rule{width:64px;height:1px;background:var(--ox);margin:26px 0}
.manifesto .overprint{position:absolute;right:20px;bottom:-35px;font:italic 150px Georgia;color:#ead3d0;opacity:.45;pointer-events:none}
.arrivals{background:#f7f6f3;padding:93px 0 105px}
.arrivals-top{display:flex;justify-content:space-between;align-items:end;margin-bottom:44px}
.arrivals h2{font-size:76px;line-height:.95;letter-spacing:-3px;color:#3f3635;margin:10px 0 0}
.arrivals-top a{font-size:12px;color:#553c40;text-decoration:none;border-bottom:1px solid #7e595e;padding-bottom:10px}
.gallery{display:grid;grid-template-columns:1.38fr 1fr 1fr 1fr;gap:15px;align-items:stretch}
.gallery-main{background:#e6dbd3;min-height:505px;overflow:hidden;position:relative}
.gallery-main::before{content:"";position:absolute;border-radius:50%;height:330px;width:520px;background:#f5ece5;top:30px;left:30px}
.gallery-main img{position:absolute;z-index:1;top:10px;width:93%;height:80%;left:3%;object-fit:contain;mix-blend-mode:multiply}
.gallery-main .new-name{position:absolute;z-index:2;bottom:38px;left:30px}
.gallery-main .new-name h3{font-size:41px;color:#4d3438;margin:10px 0 0;letter-spacing:-1px}
.gallery-main .new-name span{font-size:11px;letter-spacing:2px;color:#96636d;font-weight:700}
.gallery-side{min-height:505px;display:flex;flex-direction:column;position:relative;border-bottom:1px solid #cfc5c1;padding-bottom:23px}
.gallery-side .art{width:100%;height:392px;display:flex;align-items:center;justify-content:center;background:#eee8e2}
.gallery-side:nth-of-type(3) .art{background:#e5e8e0}
.gallery-side:nth-of-type(4) .art{background:#eadeda}
.gallery-side img{display:block;width:97%;height:96%;object-fit:contain;mix-blend-mode:multiply}
.gallery-side .meta{margin-top:19px;display:flex;align-items:end;justify-content:space-between;gap:8px}
.gallery-side h3{font-size:21px;line-height:1.2;margin:0;color:#4e393d;letter-spacing:-.3px}
.gallery-side small{display:block;margin-bottom:5px;font-size:10px;letter-spacing:1.2px;color:#857873}
.gallery-side .angle{font-size:24px;color:#8a555f}
.arrivals-note{display:flex;justify-content:space-between;font-size:10px;color:#9f8e89;margin-top:25px;letter-spacing:1.3px}
.collection-intro{display:flex;justify-content:center;align-items:center;padding:60px 0;background:#fff;font-size:10px;color:#9c7d79;letter-spacing:4px}
.ww-bottom{position:relative;overflow:hidden;width:100%;height:8298px;background:white}
.ww-bottom>img{position:absolute;display:block;left:0;width:1440px;max-width:none;top:-2260px}
.ww-bottom>img.mob{display:none}
.editorial-overlay{position:absolute;left:30px;width:1380px;height:551px;overflow:hidden;z-index:2}
.editorial-overlay.women{top:0;background:#f0e1db}
.editorial-overlay.men{top:1119px;background:#ecede6}
.editorial-overlay.couples{top:2238px;background:#553541;color:#fff}
.overlay-shell{height:100%;display:grid;grid-template-columns:53% 47%;position:relative}
.overlay-text{padding:95px 85px;position:relative;z-index:2}
.overlay-text h2{font-size:82px;line-height:1;letter-spacing:-4px;margin:25px 0;color:#5e3841}
.overlay-text .eyebrow{color:#946875}
.overlay-text p{font-size:14px;line-height:1.75;color:#74625f;max-width:330px}
.overlay-text .line{font-size:12px;color:#794754;display:inline-block;border-bottom:1px solid #925e69;padding-bottom:9px;margin-top:15px}
.overlay-art{position:relative;overflow:hidden}
.overlay-art svg{width:100%;height:100%}
.editorial-overlay.men .overlay-shell{grid-template-columns:45% 55%}
.editorial-overlay.men .overlay-text{padding:115px 70px 50px}
.editorial-overlay.men h2{color:#515650}
.editorial-overlay.men .eyebrow,.editorial-overlay.men .line{color:#606f68}
.editorial-overlay.couples .overlay-shell{grid-template-columns:50% 50%}
.editorial-overlay.couples .overlay-text{padding:95px 90px}
.editorial-overlay.couples h2,.editorial-overlay.couples .eyebrow,.editorial-overlay.couples .line{color:#fff1eb}
.editorial-overlay.couples p{color:#e8d3d8}
.editorial-overlay.couples .line{border-color:#ebc7c7}
@media(max-width:700px){
 .original img.desk{display:none}.original img.mob{display:block;width:390px}
 .orig-header{height:104px}
 .container{padding-left:20px;padding-right:20px}
 .hero{height:750px}
 .hero .hero-copy{top:49px;left:23px;max-width:345px}
 .hero h1{font-size:66px;letter-spacing:-3.5px;line-height:.99;margin:17px 0 16px}
 .hero-copy p{font-size:12px;line-height:1.65;max-width:310px}
 .eyebrow{font-size:9px;letter-spacing:2.1px}
 .cta{padding:12px 16px;min-width:220px;font-size:11px;margin-top:5px}
 .hero-art{top:320px;right:0;width:100%;height:428px;overflow:hidden}
 .hero-art svg{height:115%;max-height:none;margin-left:15px;margin-top:0}
 .hero-smallprint{bottom:11px;left:20px;font-size:7px;letter-spacing:1px}
 .quick .container{padding:22px 0 23px}
 .quick-heading{margin:0 20px 10px;padding-bottom:12px}
 .quick-heading h2{font-size:22px}
 .quick-heading .small{font-size:8px}
 .quick-list{display:flex;gap:6px;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding:1px 18px 8px}
 .quick-list::-webkit-scrollbar{display:none}
 .quick-item{flex:0 0 82px;width:82px;height:119px;padding:4px 0 9px;scroll-snap-align:start;gap:4px}
 .quick-item .art{width:80px;height:84px}
 .quick-item .name{font-size:8px;letter-spacing:.6px}
 .quick-swipe{display:flex;font-size:8px;letter-spacing:1.3px;color:#a08d8b;padding:0 20px;justify-content:space-between}
 .manifesto .container{padding-top:45px;padding-bottom:48px;grid-template-columns:1fr;gap:20px}
 .manifesto-copy{padding:0}
 .manifesto-copy h2{font-size:45px;letter-spacing:-1.7px;margin:13px 0 12px}
 .manifesto-copy p{font-size:12px}
 .manifesto-rule{margin:15px 0}
 .manifesto-art{height:255px}
 .manifesto .overprint{font-size:60px;bottom:-14px}
 .arrivals{padding:55px 0 65px}
 .arrivals-top{margin-bottom:21px}
 .arrivals h2{font-size:50px;letter-spacing:-2px}
 .arrivals-top a{font-size:10px}
 .gallery{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
 .gallery-main{grid-column:span 2;min-height:340px}
 .gallery-main::before{width:360px;height:260px;top:-25px;left:0}
 .gallery-main img{height:76%;width:86%;left:7%;top:-7px}
 .gallery-main .new-name{bottom:23px;left:22px}
 .gallery-main .new-name h3{font-size:35px}
 .gallery-side{min-height:300px;padding-bottom:12px}
 .gallery-side .art{height:225px}
 .gallery-side small{font-size:8px}
 .gallery-side h3{font-size:16px}
 .gallery-side .angle{font-size:18px}
 .gallery-side:nth-last-child(1){display:flex;grid-column:span 2;min-height:250px}.gallery-side:nth-last-child(1) .art{height:190px}
 .arrivals-note{font-size:8px}
 .collection-intro{padding:39px 0;letter-spacing:2px}
 .ww-bottom{height:7889px}
 .ww-bottom>img.desk{display:none}
 .ww-bottom>img.mob{display:block;width:390px;top:-1880px}
 .editorial-overlay{left:10px;width:370px;height:223px}
 .editorial-overlay.women{top:0}
 .editorial-overlay.men{top:925px}
 .editorial-overlay.couples{top:1849px}
 .overlay-shell{grid-template-columns:52% 48%}
 .overlay-text{padding:27px 14px}
 .overlay-text h2{font-size:36px;letter-spacing:-1.8px;margin:9px 0 6px}
 .overlay-text p{font-size:9px;line-height:1.45}
 .overlay-text .line{font-size:9px;margin-top:4px;padding-bottom:4px}
 .editorial-overlay.men .overlay-shell{grid-template-columns:52% 48%}
 .editorial-overlay.men .overlay-text{padding:34px 15px}
 .editorial-overlay.couples .overlay-shell{grid-template-columns:52% 48%}
 .editorial-overlay.couples .overlay-text{padding:29px 14px}
}

/* HOMEPAGE H2 V5: sensuous, sophisticated, seductive; standalone static design only.
   Never modify the actual WWW screenshot modules from Creator Signed Editions downward. */
.v5-hero{height:715px!important;position:relative;background:#1b060a!important;color:#fff;overflow:hidden}
.v5-hero-image{position:absolute;inset:0;width:100%;height:100%;overflow:hidden}
.v5-hero-image img{width:100%;height:100%;object-fit:cover;object-position:82% 43%;filter:brightness(.76) contrast(1.16) saturate(.83) sepia(.05);transform:scale(1.06)}
.v5-hero-veil{position:absolute;inset:0;background:linear-gradient(90deg,rgba(16,0,6,.98) 0%,rgba(32,1,13,.94) 26%,rgba(38,4,17,.55) 49%,rgba(43,2,13,.08) 80%),linear-gradient(0deg,rgba(18,0,9,.68) 0%,transparent 42%)}
.v5-hero-copy{position:absolute;z-index:3;left:max(62px,calc((100vw - 1320px)/2));top:145px;width:62%}
.v5-overline{font-size:11px;font-weight:700;letter-spacing:4px;color:#f3c8bb;text-transform:uppercase}
.v5-hero h1{font:400 118px/.94 Georgia,serif;letter-spacing:-5px;color:#fff5ec;margin:35px 0 29px;text-shadow:0 4px 22px rgba(0,0,0,.12)}
.v5-hero h1 em{font-weight:400;color:#e8a2a5}
.v5-hero-copy p{font-size:16px;line-height:1.85;color:#e1c3bf;letter-spacing:.1px;max-width:480px;margin:0 0 36px}
.v5-cta{background:#f1e1d3;color:#4c1427;display:inline-flex;min-width:272px;align-items:center;justify-content:space-between;padding:18px 26px;font-size:12px;font-weight:800;letter-spacing:1.2px}
.v5-hero-bottom{position:absolute;z-index:3;bottom:27px;left:max(62px,calc((100vw - 1320px)/2));right:40px;display:flex;justify-content:space-between;color:#d9aca9;font-size:9px;letter-spacing:2px}
.quick{background:#fff6f0!important;border-bottom:1px solid #eddfd7}
.quick .container{padding-top:30px;padding-bottom:33px}
.quick-heading h2{color:#5b2b39;font-size:27px}
.quick-heading .small{color:#ac797b}
.quick-list{gap:15px}
.quick-item .art img{filter:contrast(1.05) saturate(1.13);mix-blend-mode:multiply}
.quick-item .name{color:#6d3c49}
.v5-mood{background:#250910;min-height:550px;display:grid;grid-template-columns:52% 48%;color:#fff;overflow:hidden}
.v5-mood-picture{position:relative;min-height:550px;overflow:hidden;background:#3e0f23}
.v5-mood-picture img{width:100%;height:100%;position:absolute;inset:0;object-fit:cover;object-position:50% 50%;filter:saturate(.82) brightness(.79) contrast(1.13)}
.v5-mood-picture:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent 45%,#250910 100%)}
.v5-mood-copy{padding:92px 70px 65px 38px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center}
.v5-mood-copy .v5-overline{color:#da9aa2}
.v5-mood-copy h2{font:400 75px/.98 Georgia,serif;letter-spacing:-3px;color:#fff7ef;margin:32px 0 21px}
.v5-mood-copy h2 em{color:#d89a9f;font-weight:400}
.v5-mood-copy p{font-size:14px;line-height:1.9;color:#d9b9b7;max-width:380px;margin:23px 0 23px}
.v5-goldline{height:1px;width:72px;background:#d09889}
.v5-textlink{color:#f5ded6;font-size:11px;font-weight:700;letter-spacing:1.6px;border-bottom:1px solid #b97e80;padding-bottom:7px}
.v5-arrivals{background:#1b0b12!important;color:#fff;padding:100px 0 110px}
.v5-arrivals-heading{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:44px}
.v5-arrivals-heading h2{font:400 75px/1.03 Georgia,serif;color:#f8ebe3;letter-spacing:-3px;margin:15px 0 0}
.v5-arrivals-heading h2 em{color:#d59da5;font-weight:400}
.v5-view{font-size:11px;letter-spacing:1.4px;border-bottom:1px solid #a7797f;color:#e8b4b1;padding-bottom:10px}
.v5-product-stage{display:grid;grid-template-columns:1.12fr 1fr;gap:20px;min-height:615px}
.v5-star{background:radial-gradient(ellipse at 57% 49%,#ac717f 0%,#603343 45%,#381523 92%);overflow:hidden;position:relative;min-height:610px}
.v5-star:before{content:"";position:absolute;inset:12% 12% 24%;border:1px solid rgba(231,187,170,.22);border-radius:50%;transform:rotate(-23deg)}
.v5-star-img{position:absolute;inset:2% 7% 13%;overflow:hidden}
.v5-star-img img{height:100%;width:100%;object-fit:contain;filter:drop-shadow(0px 40px 35px rgba(0,0,0,.45));mix-blend-mode:screen}
.v5-star-info{position:absolute;bottom:26px;left:30px;right:27px;display:flex;align-items:baseline;gap:12px;justify-content:space-between}
.v5-star-info span{font-size:10px;letter-spacing:1.3px;color:#f9d4c5}
.v5-star-info h3{font:400 29px Georgia,serif;color:white;margin:0}
.v5-other-items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:repeat(2,minmax(0,1fr));gap:17px}
.v5-mini{position:relative;background:radial-gradient(ellipse at 50% 40%,#9b6577,#492134 68%,#301422);overflow:hidden;min-height:293px}
.v5-mini:nth-child(2){background:radial-gradient(ellipse at 50% 42%,#9f637e,#422235 65%,#291120)}
.v5-mini:nth-child(3){grid-column:span 2;background:radial-gradient(ellipse at 50% 43%,#767b79,#4b3342 62%,#25131b)}
.v5-mini-photo{position:absolute;inset:10px 10px 45px}
.v5-mini-photo img{width:100%;height:100%;object-fit:contain;mix-blend-mode:screen;filter:drop-shadow(0 15px 14px rgba(0,0,0,.37))}
.v5-mini-label{position:absolute;bottom:0;left:0;right:0;display:flex;align-items:baseline;gap:12px;padding:11px 16px;background:linear-gradient(0deg,#1e0b14d9,transparent)}
.v5-mini-label>span{font-size:10px;letter-spacing:1px;color:#eebbb4}
.v5-mini-label h3{font:400 20px Georgia,serif;color:#fff5ee;margin:0;flex:1}
.collection-intro{background:#300d19!important;color:#d6a5ac!important}
.editorial-overlay{border-radius:0!important}
.editorial-overlay.women{background:#4c172b!important;color:white!important}
.editorial-overlay.men{background:#e7d0c5!important;color:#502937!important}
.editorial-overlay.couples{background:#351019!important}
.editorial-overlay.women .overlay-shell,.editorial-overlay.men .overlay-shell,.editorial-overlay.couples .overlay-shell{grid-template-columns:48% 52%}
.editorial-overlay .overlay-text{padding:94px 67px}
.editorial-overlay h2{font:400 71px/.99 Georgia,serif;letter-spacing:-2.8px;margin:25px 0 22px}
.editorial-overlay.women h2,.editorial-overlay.women .overlay-text .eyebrow,.editorial-overlay.women p,.editorial-overlay.women .line{color:#f7e2de!important}
.editorial-overlay.men h2,.editorial-overlay.men .overlay-text .eyebrow,.editorial-overlay.men p,.editorial-overlay.men .line{color:#532f3e!important}
.editorial-overlay .overlay-text p{max-width:350px}
.editorial-overlay .overlay-art img{width:100%;height:100%;object-fit:cover;object-position:50% 42%;filter:brightness(.83) saturate(.77)}
.editorial-overlay.women .overlay-art{background:#ab657c}
.editorial-overlay.women .overlay-art img{object-position:50% 40%}
.editorial-overlay.men .overlay-art{background:#d2b9ad}
.editorial-overlay.men .overlay-art img{object-position:50% 35%;filter:saturate(.75) sepia(.09)}
.editorial-overlay.couples .overlay-art img{object-position:73% 48%;filter:brightness(.68) saturate(.84)}
.editorial-overlay.couples .overlay-text{padding:94px 64px}
@media(max-width:700px){
 .v5-hero{height:705px!important}
 .v5-hero-image img{object-position:65% 50%;height:100%;width:100%;transform:scale(1.02)}
 .v5-hero-veil{background:linear-gradient(180deg,rgba(21,0,7,.96) 0%,rgba(33,0,12,.92) 26%,rgba(34,0,9,.51) 52%,rgba(18,0,7,.09) 80%,rgba(18,0,9,.54) 100%)}
 .v5-hero-copy{left:21px;top:53px;width:calc(100% - 41px)}
 .v5-overline{font-size:8px;letter-spacing:2.1px}
 .v5-hero h1{font-size:64px;letter-spacing:-3px;line-height:.95;margin:20px 0 13px}
 .v5-hero-copy p{font-size:11px;line-height:1.65;max-width:260px;margin-bottom:19px}
 .v5-cta{font-size:9px;letter-spacing:.8px;min-width:235px;padding:12px 16px}
 .v5-hero-bottom{left:21px;right:17px;bottom:14px;font-size:7px;letter-spacing:1.1px}
 .quick .container{padding-top:24px;padding-bottom:20px}
 .quick-heading h2{font-size:23px}
 .quick-heading .small{font-size:8px}
 .v5-mood{grid-template-columns:1fr;min-height:auto}
 .v5-mood-picture{min-height:290px;height:290px;order:2}
 .v5-mood-picture img{object-position:50% 35%}
 .v5-mood-picture:after{background:linear-gradient(0deg,rgba(36,8,17,.17),rgba(36,8,17,.7))}
 .v5-mood-copy{order:1;padding:51px 23px 33px}
 .v5-mood-copy h2{font-size:51px;line-height:1;letter-spacing:-2px;margin:17px 0 14px}
 .v5-mood-copy p{font-size:12px;margin:17px 0}
 .v5-textlink{font-size:9px}
 .v5-arrivals{padding:54px 0 60px}
 .v5-arrivals-heading{margin-bottom:24px;gap:12px}
 .v5-arrivals-heading h2{font-size:39px;letter-spacing:-1.6px;margin-top:9px}
 .v5-view{font-size:8px;max-width:92px;line-height:1.6;text-align:right}
 .v5-product-stage{grid-template-columns:1fr;gap:11px}
 .v5-star{min-height:450px}
 .v5-star-img{inset:2% 6% 13%}
 .v5-star-info{left:17px;right:17px;bottom:18px}
 .v5-star-info h3{font-size:23px}
 .v5-star-info span{font-size:8px}
 .v5-other-items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:auto;gap:9px}
 .v5-mini{min-height:221px}
 .v5-mini:nth-child(3){min-height:232px}
 .v5-mini-photo{inset:7px 7px 41px}
 .v5-mini-label{padding:8px}
 .v5-mini-label h3{font-size:13px}
 .v5-mini-label>span{font-size:8px}
 .collection-intro{font-size:8px;letter-spacing:1.4px}
 .editorial-overlay .overlay-shell,.editorial-overlay.women .overlay-shell,.editorial-overlay.men .overlay-shell,.editorial-overlay.couples .overlay-shell{grid-template-columns:53% 47%}
 .editorial-overlay .overlay-text,.editorial-overlay.women .overlay-text,.editorial-overlay.men .overlay-text,.editorial-overlay.couples .overlay-text{padding:23px 11px}
 .editorial-overlay h2{font-size:33px;letter-spacing:-1.2px;margin:11px 0 9px}
 .editorial-overlay .overlay-text p{font-size:8.5px}
 .editorial-overlay .overlay-art img{object-position:57% center}
 .editorial-overlay.couples .overlay-art img{object-position:70% center}
 .editorial-overlay .line{font-size:8px}
}



/* Temporary standalone PHP implementation: real HTML links; no WWW screenshot elements. */
.byl-v5-page{overflow:hidden;--ink:#322c2b}
.byl-v5-page .v5-hero a.v5-cta,.byl-v5-page .v5-view,.byl-v5-page .v5-textlink{cursor:pointer;text-decoration:none}
.byl-v5-page .v5-star,.byl-v5-page .v5-mini{display:block;text-decoration:none}
.byl-v5-page .v5-view{color:#eac1bc}
.byl-v5-categories{background:#fcf9f6;padding:38px max(20px,calc((100vw - 1440px)/2)) 0}
.byl-v5-categories .editorial-overlay{position:relative!important;left:auto!important;top:auto!important;width:100%!important;max-width:1380px;margin:0 auto 48px;height:551px!important}
.byl-v5-categories .editorial-overlay .overlay-shell{min-height:100%}
.byl-v5-categories .editorial-overlay a.line{color:inherit;text-decoration:none}
.byl-v5-page .byl-v5-live-products{padding:30px 0 68px}
.byl-v5-page .byl-home-explore,.byl-v5-page .byl-home-products,.byl-v5-page .byl-home-support{position:relative}
@media(max-width:700px){
 .byl-v5-categories{padding:20px 10px 0}
 .byl-v5-categories .editorial-overlay{height:223px!important;margin-bottom:22px}
 .byl-v5-categories .overlay-text{padding:20px 12px!important}
 .byl-v5-categories .editorial-overlay h2{font-size:33px;line-height:1;letter-spacing:-1.2px;margin:10px 0}
 .byl-v5-categories .editorial-overlay .overlay-text p{font-size:9px;line-height:1.5}
 .byl-v5-page .byl-v5-live-products{padding:28px 0 46px}
}

</style>
<main id="main-content" class="byl-home byl-v5-page" data-byl-template="home-v5-temp" data-byl-git-source="v5-pr109">
<div class="v4">

<section class="hero v5-hero" id="v5-hero">
  <div class="v5-hero-image"><img loading="eager" src="https://img.beyourlover.com/2025/03/20052232/influencers-instagram-4.webp" alt="Original BeYourLover sensual fashion illustration" loading="eager"></div>
  <div class="v5-hero-veil"></div>
  <div class="v5-hero-copy">
    <span class="v5-overline">BEYOURLOVER &nbsp; / &nbsp; THE ART OF PLEASURE</span>
    <h1>Give in<br>to <em>desire.</em></h1>
    <p>There are pleasures worth losing yourself in.<br>Discover yours, beautifully.</p>
    <a class="v5-cta" href="<?php echo esc_url(home_url('/shop/')); ?>">EXPLORE THE COLLECTION <span aria-hidden="true">↗</span></a>
  </div>
  <div class="v5-hero-bottom"><span>THE BEAUTY OF FEELING EVERYTHING</span><span>01 &nbsp; / &nbsp; 03</span></div>
</section>
<section class="quick" id="v4-quick" aria-label="Original eight BeYourLover product shortcuts">
 <div class="container">
  <div class="quick-heading"><h2>The icons of pleasure.</h2><span class="small">8 ORIGINALS &nbsp; · &nbsp; EXPLORE WHAT MOVES YOU</span></div>
  <nav class="quick-list">
   <a class="quick-item" href="/skullman/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/07/21065415/skullman-banner-image1-300x300.webp" alt="Skullman"></span><span class="name">SKULLMAN</span></a>
   <a class="quick-item" href="/beyourlover-fatima-green/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/03/20052055/beyourlover-fatima.webp" alt="Fatima"></span><span class="name">FATIMA</span></a>
   <a class="quick-item" href="/clock"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/02/07075510/new-clock-main05-300x300.png" alt="Clock"></span><span class="name">CLOCK</span></a>
   <a class="quick-item" href="/beyourlover-magic-suitcase-green/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/02/09054036/magic-suitcase-pink-banner-300x300.webp" alt="Suitcase"></span><span class="name">SUITCASE</span></a>
   <a class="quick-item" href="/beyourlover-joy-cup-diamond-version/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/03/20052056/beyourlover-joy-cup.webp" alt="Joy Cup"></span><span class="name">JOY CUP</span></a>
   <a class="quick-item" href="/beyourlover-alggie/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/03/20052053/beyourlover-earphone.webp" alt="Earpod"></span><span class="name">EARPOD</span></a>
   <a class="quick-item" href="/m2/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2025/11/23013116/M2-Masturbator-Main-Image0-1-300x300.png" alt="M2"></span><span class="name">M2</span></a>
   <a class="quick-item" href="/lippy/"><span class="art"><img loading="lazy" src="https://img.beyourlover.com/2026/03/24092622/lippy-main-pictures-01-300x300.png" alt="Lippy"></span><span class="name">LIPPY</span></a>
  </nav>
  <div class="quick-swipe"><span>EXPLORE ALL EIGHT ICONS</span><span>←&nbsp;&nbsp;&nbsp;→</span></div>
 </div>
</section>
<section class="v5-mood" aria-label="Seductive editorial story">
  <div class="v5-mood-picture"><img loading="lazy" src="https://img.beyourlover.com/2026/06/01070211/influencers-instagram-29.jpg" alt="Original BeYourLover illustrated portrait" loading="eager"></div>
  <div class="v5-mood-copy"><span class="v5-overline">THE PLEASURE IS YOURS</span><h2>Some things<br>are better<br><em>felt.</em></h2><span class="v5-goldline"></span>
    <p>For curious minds, intimate moments and every delicious possibility in between.</p>
    <a class="v5-textlink" href="<?php echo esc_url(home_url('/shop/')); ?>">DISCOVER YOUR WORLD &nbsp; ↗</a>
  </div>
</section>
<section class="arrivals v5-arrivals" id="v4-arrivals">
<div class="container">
  <div class="v5-arrivals-heading"><div><span class="v5-overline">THE LATEST OBSESSIONS</span><h2>Meet your<br><em>new temptation.</em></h2></div><a class="v5-view" href="<?php echo esc_url(home_url('/new-arrivals/')); ?>">DISCOVER NEW ARRIVALS &nbsp; ↗</a></div>
  <div class="v5-product-stage">
    <a class="v5-star" href="<?php echo esc_url($byl_v5_product_link('tara-s2')); ?>">
      <div class="v5-star-img"><img loading="lazy" src="https://img.beyourlover.com/2026/08/14100230/s2-main-pictures-01-1000x1000.png" alt="TARA S2 actual product photo"></div>
      <div class="v5-star-info"><span>01 / THE OBJECT OF DESIRE</span><h3>TARA S2</h3><span>DISCOVER &nbsp; ↗</span></div>
    </a>
    <div class="v5-other-items">
      <a class="v5-mini" href="<?php echo esc_url($byl_v5_product_link('minaka')); ?>"><div class="v5-mini-photo"><img loading="lazy" src="https://img.beyourlover.com/2026/06/16065328/minaka-main-pictures.png" alt="MINAKA actual product photo"></div><div class="v5-mini-label"><span>02</span><h3>MINAKA</h3><span>↗</span></div></a>
      <a class="v5-mini" href="<?php echo esc_url($byl_v5_product_link('naughty-clock-pink')); ?>"><div class="v5-mini-photo"><img loading="lazy" src="https://img.beyourlover.com/2026/04/10042552/main-pictures-03-1.webp" alt="NAUGHTY CLOCK PINK actual product photo"></div><div class="v5-mini-label"><span>03</span><h3>CLOCK PINK</h3><span>↗</span></div></a>
      <a class="v5-mini" href="<?php echo esc_url($byl_v5_product_link('naughty-clock-green')); ?>"><div class="v5-mini-photo"><img loading="lazy" src="https://img.beyourlover.com/2025/12/23013024/naughty-clock.png" alt="NAUGHTY CLOCK GREEN actual product photo"></div><div class="v5-mini-label"><span>04</span><h3>CLOCK GREEN</h3><span>↗</span></div></a>
    </div>
  </div>
</div>
</section><div class="collection-intro">UNLOCK YOUR NEXT DESIRE &nbsp; / &nbsp; THREE WORLDS TO DISCOVER</div>
</div>
<div class="byl-v5-categories" aria-label="BeYourLover collections">
<section class="editorial-overlay women">
  <div class="overlay-shell"><div class="overlay-text"><span class="eyebrow">FOR THE MOMENTS THAT MATTER</span><h2>Let your<br>senses lead.</h2><p>The most tempting discoveries begin with a little curiosity.</p><a class="line" href="<?php echo esc_url($byl_v5_category_link('sex-toys-for-women')); ?>">EXPLORE FOR HER &nbsp; ↗</a></div>
   <div class="overlay-art"><img src="https://img.beyourlover.com/2025/03/20052232/influencers-instagram-4.webp" alt="Original BeYourLover editorial illustration" loading="eager"></div></div>
 </section>
 <section class="byl-v5-live-products byl-home-section" aria-label="Live featured products"><div class="byl-container"><div class="byl-product-grid">
 <?php foreach ($popular_products as $product): echo byl_v3_product_card($product); endforeach; ?>
 </div></div></section>
 <section class="editorial-overlay men">
  <div class="overlay-shell"><div class="overlay-text"><span class="eyebrow">UNAPOLOGETIC PLEASURE</span><h2>Make<br>your move.</h2><p>Bold in every detail. Irresistible by design.</p><a class="line" href="<?php echo esc_url($byl_v5_category_link('sex-toys-for-men')); ?>">EXPLORE FOR HIM &nbsp; ↗</a></div>
   <div class="overlay-art"><img src="https://img.beyourlover.com/2025/03/20052543/influencers-instagram-6.webp" alt="Original BeYourLover editorial illustration" loading="eager"></div></div>
 </section>
 <section class="editorial-overlay couples">
  <div class="overlay-shell"><div class="overlay-text"><span class="eyebrow">A LITTLE CLOSER</span><h2>Closer.<br>Hotter.</h2><p>A spark worth following. Made for everything that happens next.</p><a class="line" href="<?php echo esc_url($byl_v5_category_link('sex-toys-for-couples')); ?>">EXPLORE TOGETHER &nbsp; ↗</a></div>
   <div class="overlay-art"><img src="https://img.beyourlover.com/2026/09/14054606/creator-signed-edtions-scaled.png" alt="Original BeYourLover editorial illustration" loading="eager"></div></div>
 </section>
</div>
<!-- Original WWW lower DOM captured 2026-10-14. No bitmap replacement. -->
<style id="byl-www-lower-parity-css">
@scope (.byl-www-lower){
.home-blog a,.home-module7 a,.home-product-module a,.product-sort-list a{text-decoration:none}.blogHomeSwiper .swiper-text .swiper-txt1,.blogHomeSwiper .swiper-text .swiper-txt2,.hobl-right .hobl-class .hobl-txt2{text-overflow:ellipsis;-webkit-box-orient:vertical;overflow:hidden}body:has(:not(.woocommerce-breadcrumb)) .site-content{padding-top:0!important}.shoptimizer-archive>.col-full{max-width:inherit;padding:0}.mySwiper03 .swiper-button-disabled,.shoptimizer-archive .archive-header,.shoptimizer-archive .page-title{display:none}.home-banner{overflow:hidden}.home-banner img{width:100%;height:auto;vertical-align:top}.home-banner .home-swiper,.hot-product .swiper-slide{position:relative}.home-banner .swiper-pagination-bullet-active,.hot-product .swiper-scrollbar-drag{background-color:#a2394a}.home-banner .swiper-button-next,.home-banner .swiper-button-prev{color:#fff}.home-banner .swiper-pagination{bottom:10px}.product-sort-list{margin:2% auto}.elementor-element-f5e0f3e .e-con-inner{max-width:inherit}.hot-product .swiper{width:100%;padding:0 0 70px}.hot-product .swiper-slide:last-child{margin-right:0!important}.hot-product .swiper-slide img{width:100%;border-radius:20px;vertical-align:top;aspect-ratio:attr(width)/attr(height)}.hot-product-title{max-width:1140px;margin:0 auto;font-size:32px;font-weight:700;text-align:left;color:#9c3749}.hot-product .swiper-horizontal>.swiper-scrollbar{height:3px}.hot-product .hot-swiper-shadow{position:absolute;bottom:0;left:0;width:100%;height:100%;border-radius:20px;box-shadow:inset 0 -138px 60px rgba(0,0,0,.1)}.hot-product .swiper-slide a{display:block;text-decoration:none;color:inherit;position:relative;transition:opacity .3s}.hot-product .swiper-slide a:hover{opacity:.9}.home-module6,.home-module7,img.pc-show{max-width:1400px;margin:auto}.home-module7 .mySwiper03{padding-bottom:50px}.home-module7 .new-product-name{font-size:1rem;font-weight:700;color:#000;padding:20px;text-align:center;text-transform:uppercase}.home-module7 .new-product-image{aspect-ratio:1/1;overflow:hidden}.home-module7 .new-product-image img{height:100%;object-fit:contain!important;transition:transform .5s}.home-module7 .new-product-image img:hover{transform:scale(1.05)}.hobl-right{flex:1;display:flex;flex-direction:column;justify-content:space-between}.home-blog a{display:block;color:#000}.blogHomeSwiper .my-bullet-active{width:7px;height:7px;background-color:#9b3749;opacity:1}.blogHomeSwiper img{border-radius:10px 10px 0 0}.blogHomeSwiper .swiper-text{font-size:14px;text-align:left;padding:2rem 1rem 3rem}.blogHomeSwiper .swiper-text>span{display:block;line-height:1.5}.blogHomeSwiper .swiper-text .swiper-txt1{height:2.325rem;display:-webkit-box;-webkit-line-clamp:1;line-clamp:1;font-size:1.58rem;font-weight:700;margin:1rem 0;color:#a43f50}.blogHomeSwiper .swiper-text .swiper-txt2{height:4.025rem;display:-webkit-box;-webkit-line-clamp:3;line-clamp:3;font-size:.875rem}.hobl-right .hobl-class{display:flex;flex-wrap:wrap;justify-content:space-between}.hobl-right .hobl-class span{display:block}.hobl-right .hobl-class .hobl-txt{padding:1.5rem 1rem}.hobl-right .hobl-class .hobl-txt1{font-size:medium;font-weight:700;margin-bottom:.5rem;color:#a43f50}.hobl-right .hobl-class .hobl-txt2{font-size:.8rem;height:1rem;display:initial;-webkit-line-clamp:1;line-clamp:1}.elementor-12842 .elementor-element.elementor-element-82ab67b .elementor-heading-title{font-weight:700!important}.home-product-module{line-height:1.5;margin-bottom:30px}.home-product-module img{vertical-align:top}.category-module-banner img{vertical-align:top;border-radius:20px;padding:10px}.mySwiper03 .swiper-slide .txt1,.mySwiper03 .swiper-slide .txt2{font-size:.875rem;line-height:1.2;padding:10px}.mySwiper03{--swiper-navigation-size:16px}.mySwiper03 .swiper-button-next,.mySwiper03 .swiper-button-prev{width:40px;height:40px;background-color:#9b3749;color:#fff;border-radius:50%}.mySwiper03 .swiper-pagination-bullet{width:12px;height:12px;border:2px solid #000;background-color:#9b3749;position:relative;opacity:1}.mySwiper03 .swiper-pagination-bullet-active::before{content:'';position:absolute;width:8px;height:8px;border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);background:#000}.mySwiper03 .swiper-slide .txt2{text-align:right}.mySwiper03 .swiper-slide{height:auto;background-color:#fff;box-shadow:0 4px 20px rgb(0 0 0 / 8%);min-height:0;display:flex;align-items:flex-start;justify-content:center}.mySwiper03 .swiper-slide img{width:100%;object-fit:cover;margin-bottom:10px;border-radius:8px}@media (min-width:769px){.home-banner{aspect-ratio:64/25}.mobile-banner-image{display:none}.product-sort-list{display:flex;justify-content:space-evenly;max-width:1400px}.product-sort-list .sort-item{width:calc(12.5%);flex-shrink:0;text-align:center;padding:10px}.product-sort-list .sort-item img{width:80%;margin:auto;aspect-ratio:1/1;object-fit:cover}.product-sort-list .sort-item span{display:block;color:#9b3749;font-size:16px;font-weight:700;margin-top:10px;line-height:1.2}.mobile-show{display:none!important}.home-tit{font-size:29px;font-weight:700!important;letter-spacing:2px;color:#9c3749;text-align:center}.elementor-7105 .elementor-element.elementor-element-92d588a .swiper-slide{height:75vh}.elementor-slides .swiper-slide-bg{background-size:cover!important}.hot-product .swiper-horizontal>.swiper-scrollbar{width:80%;left:50%;transform:translate(-50%,0)}.elementor-12842 .elementor-element.elementor-element-f5e0f3e,.elementor-7105 .elementor-element.elementor-element-f5e0f3e{max-width:1400px;margin:0 auto}.hot-product{max-width:1400px;margin:10px auto 20px;padding:0 10px}.hot-product .swiper-slide{width:44%!important;margin-right:30px}.hot-product .swiper-text{position:absolute;bottom:10%;left:8%;font-family:Poppins;font-weight:700}.hot-product .swiper-product-tit{display:block;color:#fff;font-size:32px;font-weight:700;margin-bottom:15px;line-height:1.2;text-transform:uppercase}.hot-product .swiper-product-link{display:inline-block;text-decoration:none;font-size:12px;background-color:#a2394a;border-radius:3px;color:#fff;padding:12px 30px;letter-spacing:4px;line-height:1}body .elementor-element.elementor-products-grid ul.products li.product{padding-bottom:0}.columns-4{max-width:1380px;margin:20px auto!important}.home-blog{max-width:1400px;margin:20px auto;display:flex;justify-content:space-between}.hobl-left{width:530px;flex-shrink:0;margin-right:10px;background-color:#f1f1f1;border-radius:10px}.home-blog .hobl-item img{width:100%;object-fit:cover;border-radius:10px 10px 0 0}.hobl-right .hobl-class .hobl-item{width:32.5%;background-color:#f1f1f1;border-radius:10px;margin-bottom:10px}.hobl-right .hobl-class .hobl-item:nth-last-child(-n+3){margin-bottom:0}.category-module-banner,.product-module-banner{padding:30px}.category-module-banner img,.home-module4 .module4-banner img,.product-module-banner img{width:100%}.home-product-module .product-list{display:flex;max-width:1240px;margin:30px auto}.home-product-module .product-item{width:calc(25%);flex-shrink:0;text-align:center;padding:0 15px}.home-product-module .product-img{display:inline-block;position:relative;transition:transform .3s}.product-collect,.product-collect .commercekit-save-wishlist,.product-discount{position:absolute}.home-product-module .product-img:hover{transform:scale(.98)}.home-product-module .product-img:hover .product-collect{opacity:1}.home-product-module .product-class{display:block;color:#555;font-size:11px;text-transform:uppercase}.home-product-module .product-class:hover{color:#000}.home-product-module .product-name{display:block;color:#2c2d33;font-size:15px;font-weight:600;margin:4px 0 2px;text-transform:uppercase}.home-product-module .product-price{display:flex;justify-content:center;align-items:baseline}.home-product-module .product-price .product-old-price{font-weight:400;color:#72767c;font-size:12px;text-decoration:line-through;margin-right:8px}.home-product-module .product-price .product-new-price{color:#43454b;font-weight:400;font-size:14px}.home-product-module .product-price .product-price-active{font-size:16px;font-weight:700;color:#cf665f}.home-product-module .product-brand{font-size:16px;color:#000;margin:12px 0 22px}.product-collect{display:none;opacity:0;transition:transform .3s;top:0;right:0;background-color:#f18d63;-webkit-mask-image:url("data:image/svg+xml;charset=utf8,%3Csvg width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M4.31802 6.31802c-1.75736 1.75736-1.75736 4.60658.0 6.36398l7.68208 7.682 7.6819-7.682c1.7573-1.7574 1.7573-4.60662.0-6.36398-1.7574-1.75736-4.6066-1.75736-6.364.0L12.0001 7.63609 10.682 6.31802c-1.75738-1.75736-4.60662-1.75736-6.36398.0z%22 stroke=%22%234a5568%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22/%3E%3C/svg%3E");mask-image:url("data:image/svg+xml;charset=utf8,%3Csvg width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M4.31802 6.31802c-1.75736 1.75736-1.75736 4.60658.0 6.36398l7.68208 7.682 7.6819-7.682c1.7573-1.7574 1.7573-4.60662.0-6.36398-1.7574-1.75736-4.6066-1.75736-6.364.0L12.0001 7.63609 10.682 6.31802c-1.75738-1.75736-4.60662-1.75736-6.36398.0z%22 stroke=%22%234a5568%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22/%3E%3C/svg%3E");width:16px;height:16px;-webkit-mask-position:center;-webkit-mask-repeat:no-repeat;-webkit-mask-size:contain}.product-collect a{width:100%;height:100%;display:block;position:relative;top:0;left:0}.product-discount{background-color:#cf665f;color:#fff;min-width:inherit;min-height:inherit;font-size:11px;border-radius:3px;padding:3px 9px;opacity:.8;top:0;left:0}.home-module-title,.home-module4 .module4-cont .module4-txt1{font-size:29px;font-weight:700!important;letter-spacing:.3px;word-spacing:2px;color:#a1394a}.rating{display:flex;align-items:center;justify-content:center}.rating .rating-list{display:flex;align-items:center}.rating .rating-list svg{width:16px;height:16px;margin-right:2px}.rating .rating-text{font-size:16px}.home-module-title{margin:30px;text-align:center}.home-module3{max-width:1400px;margin:0 auto 70px}.home-module3 .models-list{display:flex;flex-wrap:wrap}.home-module3 .models-list .models-item{width:25%;flex-shrink:0;overflow:hidden;cursor:pointer}.home-module3 .models-list img{width:100%;transition:transform .3s}.home-module3 .models-list img:hover{transform:scale(1.1)}.home-module4{max-width:1400px;margin:0 auto 60px}.home-module4 .module4-banner a{display:inline-block;position:relative;color:#000}.home-module4 .module4-banner .banner-cont{position:absolute;top:50%;left:10%;transform:translate(0,-50%);line-height:1}.home-module4 .banner-text{font-size:35px;font-weight:700;margin-bottom:30px}.home-module4 .banner-but{color:#fff;display:inline-block;background-color:transparent;border-width:0;border-radius:5px;background-image:linear-gradient(180deg,#9c3749 0,#f2295b 100%);padding:12px 24px;font-size:15px}.home-module4 .module4-cont{text-align:center}.home-module4 .module4-cont .module4-txt1{margin-top:30px}.home-module4 .module4-cont .module4-txt2{width:45%;margin:10px auto;color:#3c3c3c;font-size:16px;line-height:20px}.mySwiper03 .swiper-slide{width:23%;border-radius:8px}}@media (max-width:768px){.home-banner{aspect-ratio:125/183}.pc-banner-image,.site .elementor-widget-woocommerce-products.elementor-wc-products ul.products li.product .button{display:none}.home-banner .home-swiper{--swiper-navigation-size:30px}.product-sort-list{display:flex;justify-content:space-between;flex-wrap:wrap;padding:0 2vw}.product-sort-list .sort-item{width:24%;flex-shrink:0;text-align:center;margin-bottom:4vw}.product-sort-list .sort-item:nth-last-child(-n+2){margin-bottom:0}.product-sort-list .sort-item img{width:60%;margin:auto;aspect-ratio:1/1;object-fit:cover}.product-sort-list .sort-item span{display:block;color:#9b3749;font-size:3vw;margin-top:1vw;line-height:1.2}.pc-show{display:none!important}.mobile-show .swiper-slide img{width:100%;aspect-ratio:16/30}.home-tit{font-size:19px;font-weight:700!important;letter-spacing:2px;color:#9c3749;text-align:center}.hot-product{padding-left:3%}.hot-product .swiper{padding:0 0 8vw}.hot-product .swiper-slide{width:70%!important;margin-right:15px}.hot-product .swiper-text{position:absolute;bottom:8%;left:8%;font-family:Poppins;font-weight:700}.hot-product .swiper-product-tit{display:block;color:#fff;font-size:4.2vw;font-weight:700;margin-bottom:1vw;line-height:1}.hot-product .swiper-product-link{display:inline-block;text-decoration:none;font-size:2vw;background-color:#cf665f;border-radius:3px;color:#fff;padding:1vw 2vw;letter-spacing:4px}.elementor-7105 .elementor-element.elementor-element-c6afd48{padding:0}.site .elementor-element.elementor-products-grid ul.products li.product{padding-bottom:0;text-align:center}.home-module7{margin:5vw 0 0!important}.home-module7 .new-product-name{font-size:clamp(.8125rem, .749rem + .2033vw, .875rem)}.columns-4{padding:0 5px;margin-bottom:6vw}.home-blog{margin:20px auto}.hobl-left,.hobl-right .hobl-class .hobl-item{background-color:#f1f1f1;border-radius:10px;margin-bottom:10px}.category-module-banner img,.hobl-left,.home-module3 .models-item img,.home-product-module .product-img img,.product-module-banner img{width:100%}.hobl-right .hobl-class .hobl-txt1{font-size:medium;font-weight:700}.home-blog .hobl-item img{width:100%;border-radius:10px 10px 0 0;object-fit:cover}.hobl-right .hobl-class .hobl-item{width:49%}.home-product-module .product-list{display:flex;flex-wrap:wrap;justify-content:space-between;margin:3vw 10px}.home-product-module .product-item{width:48%;flex-shrink:0;text-align:center;margin-bottom:3vw}.home-product-module .product-img{display:inline-block;position:relative;transition:transform .3s}.home-product-module .product-class{display:block;color:#555;font-size:3vw;text-transform:uppercase}.home-product-module .product-class:hover{color:#000}.home-product-module .product-name{display:block;color:#2c2d33;font-size:4vw;font-weight:600;margin:4px 0 2px;text-transform:uppercase}.home-product-module .product-price{display:flex;justify-content:center;align-items:baseline}.home-product-module .product-price .product-old-price{font-weight:400;color:#72767c;font-size:3vw;text-decoration:line-through;margin-right:8px}.home-product-module .product-price .product-new-price{color:#43454b;font-weight:400;font-size:3.5vw}.home-product-module .product-price .product-price-active{font-size:3.5vw;font-weight:700;color:#cf665f}.home-product-module .product-brand{font-size:3.5vw;color:#000;margin:12px 0 22px}.product-collect{display:none;position:absolute;top:25px;right:10%;background-color:#f18d63;-webkit-mask-image:url("data:image/svg+xml;charset=utf8,%3Csvg width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M4.31802 6.31802c-1.75736 1.75736-1.75736 4.60658.0 6.36398l7.68208 7.682 7.6819-7.682c1.7573-1.7574 1.7573-4.60662.0-6.36398-1.7574-1.75736-4.6066-1.75736-6.364.0L12.0001 7.63609 10.682 6.31802c-1.75738-1.75736-4.60662-1.75736-6.36398.0z%22 stroke=%22%234a5568%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22/%3E%3C/svg%3E");mask-image:url("data:image/svg+xml;charset=utf8,%3Csvg width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cpath d=%22M4.31802 6.31802c-1.75736 1.75736-1.75736 4.60658.0 6.36398l7.68208 7.682 7.6819-7.682c1.7573-1.7574 1.7573-4.60662.0-6.36398-1.7574-1.75736-4.6066-1.75736-6.364.0L12.0001 7.63609 10.682 6.31802c-1.75738-1.75736-4.60662-1.75736-6.36398.0z%22 stroke=%22%234a5568%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22/%3E%3C/svg%3E");width:16px;height:16px;-webkit-mask-position:center;-webkit-mask-repeat:no-repeat;-webkit-mask-size:contain}.product-discount{background-color:#cf665f;color:#fff;min-width:inherit;min-height:inherit;font-size:2.5vw;border-radius:3px;padding:3px 9px;opacity:.8;position:absolute;top:0;left:0}.rating{display:flex;align-items:center;justify-content:center}.rating .rating-list{display:flex;align-items:center}.rating .rating-list svg{width:3.5vw;height:3.5vw;margin-right:2px}.rating .rating-text{font-size:3.5vw}.home-module-title{color:#9c3749;font-size:19px;font-weight:700!important;margin-bottom:3vw;text-align:center}.home-module3{margin-bottom:7vw}.home-module3 .models-list{display:flex;flex-wrap:wrap}.home-module3 .models-list .models-item{width:50%;flex-shrink:0}.home-module4{margin-bottom:6vw}.home-module4 .module4-banner a{display:inline-block;position:relative;color:#000}.home-module4 .module4-banner img{width:100%;object-fit:cover}.home-module4 .module4-banner .banner-cont{position:absolute;top:14%;left:10%;line-height:1}.home-module4 .banner-text{font-size:6vw;font-weight:700;margin-bottom:5vw}.home-module4 .banner-but{color:#fff;display:inline-block;background-color:transparent;border-width:0;border-radius:5px;background-image:linear-gradient(180deg,#9c3749 0,#f2295b 100%);padding:12px 24px;font-size:3.8vw}.home-module4 .module4-cont{text-align:center}.home-module4 .module4-cont .module4-txt1{font-size:19px;font-weight:700!important;letter-spacing:.3px;word-spacing:2px;color:#a1394a;margin-top:30px}.home-module4 .module4-cont .module4-txt2{width:98%;margin:2vw auto;color:#3c3c3c;font-size:3.8vw;line-height:20px}.home-module6{margin-bottom:4vw}.mySwiper03 .swiper-slide{width:70%;border-radius:8px}}.endorsement-module{margin:80px 0}.carousel-namespace{display:block;width:100vw;overflow:hidden;margin:0 auto}.carousel-namespace .carousel-stage{width:100%;backdrop-filter:blur(2px);padding:2.25rem .5rem;}.carousel-namespace .belt-track{overflow:hidden;position:relative;width:100%;cursor:grab;touch-action:pan-y pinch-zoom}.carousel-namespace .belt-track:active{cursor:grabbing}.carousel-namespace .belt-scroll{display:flex;align-items:center;will-change:transform;width:max-content;transform:translateX(0)}.carousel-namespace .icon-item{display:flex;flex-direction:column;align-items:center;justify-content:center;margin:0 45px;backdrop-filter:blur(12px);transition:.25s;cursor:pointer}.carousel-namespace .icon-svg{display:flex;align-items:center;justify-content:center}.carousel-namespace .icon-svg img{width:auto;max-height:70px;object-fit:cover}@media (max-width:700px){.endorsement-module{margin:10vw 0}.carousel-namespace .carousel-stage{padding:3vw 2vw}.carousel-namespace .icon-item{margin:0 3vw;padding:10px 14px;min-width:80px}.carousel-namespace .icon-svg img{width:auto;max-height:12vw}}.creator-signed-edtions{max-width: 1400px;margin: auto;}@media (min-width:769px) {.creator-signed-edtions{margin-bottom: 70px;}}@media (max-width:768px) {.creator-signed-edtions{margin-bottom: 7vw;}}
/* 调用于footer-global.php */
#colophon{
    color: #000;
    background-color: #F5F5F7;
    padding: 2rem 0.6rem 1rem;
}
#colophon .col-full{
    max-width: 1170px;
    display: block;
    margin: auto;
}
.footer-payment{
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}
.footer-payment .footer-payment-left{
    display: flex;
}
.footer-payment .footer-payment-left .yay-currency-shortcode-switcher{
    margin-right: 20px;
}

.guarantee-item img {
    display: inline-block;
    max-width: 100%;
    height: auto;
}

@media (max-width: 768px) {
    .guarantee-item img {
        height: 8vw;
        margin-bottom: 2vw;
    }
    #colophon{
        padding: 2rem 0.6rem 1rem;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .guarantee-item img {
        height: 24px;
    }
    .guarantee-list {
        padding: 15px 0;
    }
}

@media (min-width: 1025px) {
    .guarantee-item img {
        height: 40px;
    }
}
@media only screen and (min-width: 769px) {
    .footer-model04 .guarantee-list {
        padding: 30px 0;
        display: flex;
        justify-content: space-between;
        border-bottom: 1px solid #D9D9D9;
    }

    .footer-model04 .guarantee-list .guarantee-item {
        text-align: center;
    }

    .footer-model04 .guarantee-item img {
        display: inline-block;
        height: 40px;
        margin-bottom: 20px;
    }

    .footer-model04 .guarantee-item .txt1 {
        font-size: 14px;
        font-weight: bold;
        color: #A74153;
        margin-top: -10px;
    }
}

@media (max-width: 768px) {
    .footer-model04 .guarantee-list{
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        border-bottom: 1px solid #D9D9D9;
        padding: 4vw 0;
    }
    .footer-model04 .guarantee-list .guarantee-item{
        width: 50%;
        flex-shrink: 0;
        text-align: center;
        margin-bottom: 4vw;
    }
    .footer-model04 .guarantee-item img{
        display: inline-block;
        height: 10vw;
        margin-bottom: 2vw;
    }
    .footer-model04 .guarantee-item .txt1{
        font-size: 14px;
        font-weight: bold;
        color: #A74153;
        font-family: math;
    }
}
.elementor .col-full {
    box-sizing: content-box !important;}


.footer-email .footer-email-txt1{
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--e-global-color-primary);
    line-height: 1;
}
.footer-email .footer-email-txt2{
    font-size:15px;
    line-height: 1;
    margin-top: 1.21575rem;
}
.footer-email #wpforms-15708{
    margin: 0;
}
#wpforms-form-15708{
    display: flex;
}
#wpforms-form-15708 .wpforms-submit-container{
    margin-top: 0;
    margin-left: 10px;
}
#wpforms-form-15708 .wpforms-submit{
    background-color: #9b3749;
    text-transform: uppercase;
    width: 100%;
    height: 100%;
    outline: none;
}
#wpforms-form-15708 .wpforms-submit:focus:after{
    border: none;
}
#wpforms-15708-field_2-container{
    display: flex;
    padding: 0;
}
#wpforms-15708-field_2-container .wpforms-field-label{
    display: none;
}
@media only screen and (min-width: 769px) {
    .footer-email{
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 50px 0 15px;
    }
}
@media (max-width: 768px) {
    .footer-email{
        padding: 50px 0 15px;
    }
    .footer-email .footer-email-text{
        text-align: center;
        margin-bottom: 1.21575rem;
    }
    #wpforms-form-15708 .wpforms-field-container{
        width: 50%;
    }
    #wpforms-form-15708 .wpforms-submit-container{
        width: 50%;
    }
}

.footer-model04{
    background-color: #F5F5F7;
}
@media only screen and (min-width: 769px) {
    /*  */
    .footer-model04 .footer-class-list {
        display: flex;
        padding: 20px 0;
    }

    .footer-model04 .footer-class-list .footer-class-item {
        width: 20%;
        flex-shrink: 0;
    }

    .footer-model04 .footer-class-list .footer-class-item:first-child {
        width: 25%;
        flex-shrink: 0;
        margin-right: 5%;
    }

    .footer-model04 .footer-class-list .footer-class-item:last-child {
        width: 30%;
        flex-shrink: 0;
    }

    .footer-model04 .footer-class-item .txt1 {
        font-size: 18px;
        font-weight: bold;
        color: #A2394A;
        margin-bottom: 15px;
    }

    .footer-model04 .footer-class-item .txt2 {
        color: #000000;
        margin-top: 10px;
        font-size: 16px;
    }

    .footer-model04 .footer-class-item .txt3 {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }

    .footer-model04 .footer-class-item .txt3 svg {
        width: 17px;
        fill: #222222;
        margin-right: 5px;
    }

    .footer-model04 .footer-class-item a {
        text-decoration: none;
        display: block;
        color: #000000;
        margin-top: 10px;
        font-size: 16px;
    }

    .footer-model04 .footer-class-item a:hover {
        color: #A2394A;
        text-decoration: underline;
    }
}

@media (max-width: 768px) {
    .footer-model04 .footer-class-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .footer-model04 .footer-class-list .footer-class-item {
        width: 48%;
        flex-shrink: 0;
        margin-bottom: 5vw;
    }

    .footer-model04 .footer-class-item .txt1 {
        font-size: 4vw;
        font-weight: bold;
        color: #A2394A;
        margin-bottom: 2vw;
    }

    .footer-model04 .footer-class-item .txt2 {
        color: #000000;
        margin-top: 2vw;
        font-size: 2.5vw;
    }

    .footer-model04 .footer-class-item .txt3 {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
        color: #000000;
        margin-top: 2vw;
        font-size: 2.5vw;
    }

    .footer-model04 .footer-class-item .txt3 svg {
        width: 3vw;
        fill: #222222;
        margin-right: 5px;
    }

    .footer-model04 .footer-class-item a {
        text-decoration: none;
        display: block;
        color: #000000;
        margin-bottom: 2vw;
        font-size: 2.5vw;
    }

    .footer-model04 .footer-class-item a:hover {
        color: #A2394A;
        text-decoration: underline;
    }
}

.footer-social-media {
    border-top: 1px solid #f0f0f0;
    border-bottom: 1px solid #f0f0f0;
    padding: 20px 0;
    margin: 20px 0;
}

/* SEO链接区域样式 */
.seo-link-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    padding: 20px 0;
}

.seo-link-header {
    display: flex;
    justify-content: center;
    text-align: center;
}

.seo-link-header h3 {
    text-transform: uppercase;
    padding-bottom: 6px;
    margin: 0;
}

.seo-link-header a {
    overflow-wrap: anywhere;
}

/* 新增链接容器样式 */
.seo-link-items {
    display: flex;
    flex-wrap: wrap; /* 允许换行 */
    align-items: center;
    gap: 8px; /* 统一间距 */
    width: 100%;
}

/* 链接项样式 */
.seo-link-items a {
    white-space: nowrap; /* 单个链接不换行 */
    display: inline-flex;
    align-items: center;
}

/* 分隔线样式 */
.seo-link-items a:not(:last-child)::after {
    content: "|";
    margin-left: 8px;
    color: rgba(0,0,0,0.3);
}

.tag-label {
    font-weight: 700;
    color: #000;
}

.so-tab {
    position: relative;
    width: 100%;
    overflow: hidden;
    margin: 25px 0;
    border: 1px solid #E1E3E4;
    border-radius: 5px;
}

.so-tab label {
    position: relative;
    display: block;
    padding: 10px;
    line-height: normal;
    cursor: pointer;
}

.so-tab input {
    position: absolute;
    opacity: 0;
    z-index: -1;
}

.so-tab-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height .35s;
    overflow-y: auto;
    padding: 0 10px;
}

.so-tab-content::-webkit-scrollbar {
    width: 4px;
}

.so-tab-content::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.so-tab-content::-webkit-scrollbar-thumb {
    background: #888;
}

/* :checked */
.so-tab input:checked~.so-tab-content {
    max-height: 200px;
    overflow-y: auto;
}

/* Icon */
.so-tab label::after {
    position: absolute;
    right: 0;
    top: 0px;
    display: block;
    width: 3em;
    height: 3em;
    line-height: 3;
    text-align: center;
    -webkit-transition: all .35s;
    -o-transition: all .35s;
    transition: all .35s;
}

.so-tab input[type=checkbox]+label::after {
    content: "+";
}

.so-tab input[type=radio]+label::after {
    content: "\25BC";
}

.so-tab input[type=checkbox]:checked+label::after {
    transform: rotate(315deg);
}

.so-tab input[type=radio]:checked+label::after {
    transform: rotateX(180deg);
}

.footer__container {
    text-align: left;
    padding: 10px 0;
}

.copyright-text {
    color: #7A7A7A;
    font-size: 13px;
    display: block;
    margin-bottom: 10px;
}

.policies {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.policies li {
    display: inline-block;
    margin-right: 5px;
    white-space: nowrap;
}

.policies li:not(:last-child)::after {
    content: "·";
    margin-left: 8px;
    color: #7a7a7a;
}

.site-footer .policies a {
    text-decoration: none !important;
    color: #045688;
    font-size: small;
}

.site-footer .policies a:hover {
    color: #000 !important;
}

@media (max-width: 768px) {
    .footer__container {
        text-align: center;
    }

    .policies {
        justify-content: center;
    }
}

.inline-list li {
    display: inline-block;
    margin-bottom: 0;
    vertical-align: middle;
}
.social-media-cont{
    --grid-template-columns: repeat(0, auto);
    --icon-size: 20px;
    --grid-column-gap: 5px;
    --grid-row-gap: 0px;
    --e-global-color-primary: #9C3749;
}
.social-media-cont {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    -webkit-font-smoothing: antialiased;
    margin: 15px 0;
}

.social-media-cont a {
    text-decoration: none;
}

.social-media-cont .tit1 {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 20px;
}

.soc-me-left .follow-list {
    display: flex;
}

.soc-me-left .follow-icon {
    font-size: var(--icon-size, 25px);
    height: calc(var(--icon-size, 25px) + 2* var(--icon-padding, .5em));
    line-height: var(--icon-size, 25px);
    width: calc(var(--icon-size, 25px) + 2* var(--icon-padding, .5em));
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    text-align: center;
    margin-right: 0;
}

.soc-me-left .follow-icon svg {
    fill: var(--e-global-color-primary);
    display: block;
    height: 1em;
    position: relative;
    width: 1em;
}

@media (min-width: 769px) {
    .footer-social-media .appStoreBadges__column {
        align-items: center;
        display: flex;
        flex-flow: row nowrap;
        justify-content: flex-end;
    }

    .social-media-cont {
        display: flex;
        justify-content: space-between;
    }

    .social-media-cont .soc-me-right .tit1 {
        text-align: right;
    }
}

@media (max-width: 768px) {
    .footer-social-media .appStoreBadges__column {
        display: flex;
        align-items: center;
		justify-content: space-between;
    }
	.footer__download{
		margin-left: 0;
	}
}

@media (max-width: 768px) {
    .footer__container {
        text-align: center;
    }

    .policies {
        justify-content: center;
    }
}

.payment-icons {
    cursor: default;
    user-select: none;
    background-color: transparent;
    margin-top: 0px;
    text-align: right;
}

.payment-icons li {
    display: inline-block;
    margin: 0 0px;
    padding-top: 0px;
}

.inline-list li {
    display: inline-block;
    margin-bottom: 0;
    vertical-align: middle;
}


/* APP下载模块CSS */
.footer__download {
    display: grid;
    grid-template-columns: 50px auto;
    align-items: center;
    background: #000;
    border: 1px solid #e1e3e4;
    color: #fff !important;
    padding: 4px 10px;
    border-radius: 8px;
    margin-left: 1em;
    transition: all .3s ease;
    max-width: 170px;
    text-decoration: none;
    cursor: pointer;
}

.footer__download-logo {
    grid-row: 1 / 3;
}

.footer__download-title {
    font: 500 12px/16px Inter, sans-serif;
}

.footer__download-name {
    box-sizing: border-box;
}

.footer__download--google {
    max-width: 170px;
}

.footer__download--appstore {
    max-width: 160px;
}

.single-product .site-content .site-footer {
    background-color: #ffffff00;
}

.blu-accordion__content h2, .blu-accordion__content h3, .blu-accordion__content p { text-align: center; }

/* 底部表单修改2.0 */
.footer-newsletter p{
	display: flex;
	justify-content: space-between;
	gap: 10px;
}
.footer-newsletter .wpcf7-form-control-wrap{
	display: block;
}
.footer-newsletter .wpcf7-form-control-wrap input{
	height: 100%;
}
.wpcf7-spinner{
	display: none;
}
@media (max-width: 768px) {
	.footer-newsletter, .footer-newsletter p{
		flex-direction: row !important;
	}
	.footer-newsletter .wpcf7-form-control-wrap,
	.footer-newsletter input[type=submit]{
		width: 49% !important;
		flex-shrink: 0;
	}
	.footer-newsletter .wpcf7-form-control-wrap input{
		width: 100%;
	}
}
/* 底部表单修改3.0 */
#wpforms-form-26680{
	display: flex;
}
.wpforms-container #wpforms-form-26680 .wpforms-field{
	padding: 0;
	margin-right: 10px;
}
.wpforms-container #wpforms-form-26680 .wpforms-field input{
	width: 100%;
	max-width: inherit;
}
#wpforms-form-26680 .wpforms-submit-container{
	margin-top: 0;
	width: 40%;
}
}


.byl-www-lower{font:14px/1.5 Poppins,Arial,Helvetica,sans-serif;color:#302d30;background:#fff}
.byl-www-lower *,.byl-www-lower *::before,.byl-www-lower *::after{box-sizing:border-box}
.byl-www-content{max-width:1440px;width:100%;margin:0 auto;padding:0 10px}
.byl-www-content img{max-width:100%;height:auto}
.byl-www-content .creator-signed-edtions{max-width:1400px;margin:0 auto}
.byl-www-content .creator-signed-edtions>a,.byl-www-content .creator-signed-edtions picture,.byl-www-content .creator-signed-edtions img{display:block;width:100%}
.byl-www-content .home-module-title{font-family:Poppins,Arial,sans-serif;text-align:center}
.byl-www-content .home-module3 .models-list{display:flex;flex-wrap:wrap}
.byl-www-content .home-module3 .models-item{width:25%;flex:0 0 25%}
.byl-www-content .home-module3 .models-item a,.byl-www-content .home-module3 .models-item img{display:block;width:100%}
.byl-www-content .home-module6{max-width:1400px;margin:0 auto}
.byl-www-content .home-module6 .mySwiper03{position:relative;width:100%;overflow:hidden;padding-bottom:45px}
.byl-www-content .home-module6 .myswiper-wrapper03{display:flex;gap:10px;overflow-x:auto;overflow-y:hidden;scroll-snap-type:x mandatory;scroll-behavior:smooth;scrollbar-width:none;touch-action:pan-x;transform:none!important}
.byl-www-content .home-module6 .myswiper-wrapper03::-webkit-scrollbar{display:none}
.byl-www-content .home-module6 .swiper-slide{width:calc((100% - 30px)/4)!important;min-width:0;flex:0 0 calc((100% - 30px)/4);margin:0!important;scroll-snap-align:start;height:auto}
.byl-www-content .home-module6 .swiper-slide img{display:block;width:100%;aspect-ratio:1;object-fit:cover}
.byl-www-content .home-module6 .swiper-button-next,.byl-www-content .home-module6 .swiper-button-prev{position:absolute;top:40%;z-index:4;width:37px;height:37px;display:grid;place-items:center;background:#9b3749;color:#fff;border:0;border-radius:50%;cursor:pointer}
.byl-www-content .home-module6 .swiper-button-next{right:4px}.byl-www-content .home-module6 .swiper-button-prev{left:4px}
.byl-www-content .home-module6 .swiper-button-next:after{content:'›';font-size:26px;line-height:1}
.byl-www-content .home-module6 .swiper-button-prev:after{content:'‹';font-size:26px;line-height:1}
.byl-www-content .home-module6 .swiper-pagination{position:absolute;bottom:7px;left:0;right:0;display:flex;align-items:center;justify-content:center;gap:7px}
.byl-www-content .home-module6 .swiper-pagination-bullet{appearance:none;width:10px;height:10px;display:block;background:#e2d6d8;border:1px solid #9b3749;padding:0;border-radius:50%;cursor:pointer}
.byl-www-content .home-module6 .swiper-pagination-bullet-active{background:#9b3749}
.byl-www-content .home-blog{max-width:1400px;margin:20px auto}
.byl-www-content .home-blog .swiper-wrapper{display:flex;overflow:hidden}
.byl-www-content .home-blog .swiper-slide{flex:0 0 100%}
.byl-www-content .home-blog img{width:100%;height:auto;display:block}
.byl-www-content .endorsement-module{max-width:1400px;margin:0 auto;overflow:hidden}
.byl-www-content .endorsement-module .belt-track{width:100%;overflow:hidden}
.byl-www-content .endorsement-module .belt-scroll{display:flex;gap:25px;width:max-content;transform:none!important;animation:byl-www-belt 38s linear infinite}
.byl-www-content .endorsement-module .belt-group{display:flex;gap:25px}
.byl-www-content .endorsement-module .icon-item{display:flex;min-width:125px;align-items:center;justify-content:center}
.byl-www-content .endorsement-module .icon-svg img{display:block;max-width:132px;max-height:65px;object-fit:contain}
@keyframes byl-www-belt{to{transform:translateX(-33.333%)}}
.byl-www-footer-wrapper{background:#f6f6f7;margin:0;padding:0}
.byl-www-footer-wrapper .site-footer{display:block;width:100%}
.byl-www-footer-wrapper .global-footer-container{max-width:1400px;margin:0 auto}
.byl-www-footer-wrapper .byl-www-newsletter-fields{display:flex;gap:10px;align-items:center}
.byl-www-footer-wrapper .byl-www-newsletter-fields input{background:#fff;color:#777;border:1px solid #ddd;min-width:140px;min-height:42px;padding:11px 14px;flex:1;border-radius:0}
.byl-www-footer-wrapper .byl-www-newsletter-fields button{border:0;background:#9b3749;color:#fff;font-size:12px;padding:13px 18px;opacity:1;cursor:default}
.byl-www-footer-wrapper .byl-www-newsletter-preview{max-width:435px}
.byl-www-footer-wrapper .byl-www-static-currency{font-size:12px}
@media(max-width:768px){
.byl-www-content{padding:0 10px}
.byl-www-content .home-module3 .models-item{flex:0 0 25%}
.byl-www-content .home-module6 .swiper-slide{width:83%!important;flex:0 0 83%}
.byl-www-content .home-blog{flex-wrap:wrap}
.byl-www-content .home-blog .hobl-left,.byl-www-content .home-blog .hobl-right{width:100%;margin-right:0}
.byl-www-footer-wrapper .global-footer-container{padding:0 12px}
.byl-www-footer-wrapper .byl-www-newsletter-fields{gap:5px}
.byl-www-footer-wrapper .byl-www-newsletter-fields input{min-width:0}
}
@media(prefers-reduced-motion:reduce){.byl-www-content .endorsement-module .belt-scroll{animation:none}.byl-www-content .home-module6 .myswiper-wrapper03{scroll-behavior:auto}}

</style>
<div class="byl-www-lower byl-www-content" data-www-source="2026-10-14">
<div class="creator-signed-edtions" role="region" aria-label="CREATOR SIGNED EDITIONS">
	<div class="home-module-title">CREATOR SIGNED EDITIONS</div>
	<a href="/creator-signed-editions" aria-label="CREATOR SIGNED EDITIONS">
		<picture>
			<source media="(min-width: 769px)" srcset="https://img.beyourlover.com/2026/09/14054606/creator-signed-edtions-scaled.png" type="image/webp">
			<source media="(max-width: 768px)" srcset="https://img.beyourlover.com/2026/09/14054606/creator-signed-edtions-scaled.png" type="image/webp">
			<img src="https://img.beyourlover.com/2026/09/14054606/creator-signed-edtions-scaled.png" alt="Sex Toys for Couples - BeYourLover Collection" width="1400" height="800" loading="eager" fetchpriority="high" decoding="async">
		</picture>
	</a>
</div>
<div class="home-product-module home-module3" role="region" aria-label="Follow us on Instagram - Influencer Gallery">
    <div class="home-module-title">FOLLOW US ON INSTAGRAM</div>
    <div class="models-list" role="list" aria-label="Instagram posts gallery">
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DLAk852sA8i/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 1">
                <img src="https://img.beyourlover.com/2026/06/16071043/influencers-instagram-25.png" alt="BeYourLover influencer Instagram post 1" width="300" height="300" loading="eager" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DHAP7CQxvXw/?hl=en&amp;img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 2">
                <img src="https://img.beyourlover.com/2025/03/20052600/influencers-instagram-8.webp" alt="BeYourLover influencer Instagram post 2" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DHMK-Uxvp0h/?hl=en&amp;img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 3">
                <img src="https://img.beyourlover.com/2025/03/20052224/influencers-instagram-3.webp" alt="BeYourLover influencer Instagram post 3" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DGwX6nCOiUE/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 4">
                <img src="https://img.beyourlover.com/2025/03/20052232/influencers-instagram-4.webp" alt="BeYourLover influencer Instagram post 4" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DIzooDqM3sn/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 5">
                <img src="https://img.beyourlover.com/2025/06/21035747/influencers-instagram-28.jpg" alt="BeYourLover influencer Instagram post 5" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DHI9TNWubFo/?hl=en&amp;img_index=2" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 6">
                <img src="https://img.beyourlover.com/2025/03/20052543/influencers-instagram-6.webp" alt="BeYourLover influencer Instagram post 6" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DY1MMWqFU6N/?img_index=2" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 7">
                <img src="https://img.beyourlover.com/2026/06/01070211/influencers-instagram-29.jpg" alt="BeYourLover influencer Instagram post 7" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
        <div class="models-item" role="listitem">
            <a href="https://www.instagram.com/p/DIBv9LQsj9w/?img_index=2" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post 8">
                <img src="https://img.beyourlover.com/2025/06/21035736/influencers-instagram-27.jpg" alt="BeYourLover influencer Instagram post 8" width="300" height="300" loading="lazy" decoding="async">
            </a>
        </div>
    </div>
</div>
<div class="home-product-module home-module6" role="region" aria-label="Customer Testimonials and Influencer Reviews">
    <div class="home-module-title">YOU &amp; BEYOURLOVER</div>
    <div class="swiper mySwiper03" aria-label="Testimonials carousel">
        <div class="swiper-wrapper myswiper-wrapper03" aria-live="off">
            <!-- Slide 1 -->
            <div class="swiper-slide" role="group" aria-label="1 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DKkQmHfKvoZ/" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by solostella_sg">
                        <img src="https://img.beyourlover.com/2026/06/16070501/redman-img07.png" alt="Customer testimonial by solostella_sg - BeYourLover" width="400" height="400" loading="eager" fetchpriority="high" decoding="async">
                    </a>
                    <div class="txt1">
                        "Hey please, can you tell what time is it?" Sure! Look here, this clock shows you the hour, bu
                        it's really helpful for something else too 🤭💖
                    </div>
                    <div class="txt2">@solostella_sg</div>
                </div>
            </div>
            <!-- Slide 2 -->
            <div class="swiper-slide" role="group" aria-label="2 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DKbjISyIT-g/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by little_neptune_">
                        <img src="https://img.beyourlover.com/2026/06/16065526/redman-img.png" alt="Customer testimonial by little_neptune_ - BeYourLover" width="400" height="400" loading="lazy" decoding="async">
                    </a>
                    <div class="txt1">
                        I love it soooo much, it's super aesthetic and super pleasing at the same time 🥰
                    </div>
                    <div class="txt2">@little_neptune_</div>
                </div>
            </div>
            <!-- Slide 3 -->
            <div class="swiper-slide" role="group" aria-label="3 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DJ3talktiQy/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by waleryasg__">
                        <img src="https://img.beyourlover.com/2026/06/16070025/redman-img05.png" alt="Customer testimonial by waleryasg__ - BeYourLover" width="400" height="400" loading="lazy" decoding="async">
                    </a>
                    <div class="txt1">
                        In love with my new toy! It's the best toy I've ever had!!
                    </div>
                    <div class="txt2">@waleryasg__</div>
                </div>
            </div>
            <!-- Slide 4 -->
            <div class="swiper-slide" role="group" aria-label="4 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DJwbCPasciz/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by mamura_senpai">
                        <img src="https://img.beyourlover.com/2026/06/16071333/redman-img04.png" alt="Customer testimonial by mamura_senpai - BeYourLover" width="400" height="400" loading="lazy" decoding="async">
                    </a>
                    <div class="txt1">
                        I chose one of their discreet toys, which goes for my D.Va cosplay photoshoot with its cute
                        suitcase design, but there are so many to choose from! The material is so soft and the colors
                        are so cute, beautifully packaged too!
                    </div>
                    <div class="txt2">@mamura_senpai</div>
                </div>
            </div>
            <!-- Slide 5 -->
            <div class="swiper-slide" role="group" aria-label="5 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DJen0T1Jn8N/?img_index=3" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by muwun_lite">
                        <img src="https://img.beyourlover.com/2026/06/16070715/redman-img03.png" alt="Customer testimonial by muwun_lite - BeYourLover" width="400" height="400" loading="lazy" decoding="async">
                    </a>
                    <div class="txt1">
                        Wake up and wind down with the only alarm clock that buzzes you awake and rocks you to sleep.
                        Discreet, rechargeable, and dual-function, it's the ultimate way to start—and end—your day
                        right!
                    </div>
                    <div class="txt2">@muwun_lite</div>
                </div>
            </div>
            <!-- Slide 6 -->
            <div class="swiper-slide" role="group" aria-label="6 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DI93CDRyCex/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by r_rqueen">
                        <img src="https://img.beyourlover.com/2025/06/21033903/redman-img02.jpg" alt="Customer testimonial by r_rqueen - BeYourLover" width="400" height="400" loading="lazy" decoding="async">
                    </a>
                    <div class="txt1">
                        It's time to put yourself first and listen to what your heart is telling you.<br>
                        Let Heart remind you that your desires matter. Feel every beat pulse with passion and reawaken
                        your senses.<br>
                        Beautifully packaged and luxuriously soft!
                    </div>
                    <div class="txt2">@r_rqueen</div>
                </div>
            </div>
            <!-- Slide 7 -->
            <div class="swiper-slide" role="group" aria-label="7 / 7">
                <div class="red-man-box">
                    <a href="https://www.instagram.com/p/DIZcdfmuLqh/?img_index=1" target="_blank" rel="noopener noreferrer" aria-label="View Instagram post by mamaalaultima">
                        <img src="https://img.beyourlover.com/2025/06/21033854/redman-img01.jpg" alt="Customer testimonial by mamaalaultima - BeYourLover" width="400" height="400" loading="lazy" decoding="async">
                    </a>
                    <div class="txt1">
                        Busca un hombre que sepa comer caracoles en salsa y si no lo encuentras aquí te dejo los nuevos
                        juguetes de @beyourlovertoys su diseño es una pasada.
                    </div>
                    <div class="txt2">@mamaalaultima</div>
                </div>
            </div>
        </div>
        <div class="swiper-button-next" aria-label="Next slide" role="button"></div>
        <div class="swiper-button-prev" aria-label="Previous slide" role="button"></div>
        <div class="swiper-pagination swiper-pagination-bullets swiper-pagination-horizontal" aria-label="Carousel pagination"></div>
    </div>
</div>
<div class="home-module-title" id="lovers-journal">LOVER'S JOURNAL</div>
<div class="home-blog" role="region" aria-label="Blog posts and categories">

    <!-- 左侧轮播 -->
    <div class="hobl-left">
        <div class="swiper blogHomeSwiper" aria-label="Featured blog posts carousel">
            <div class="swiper-wrapper blogHomeSwiper-wrapper" id="blog-posts">
                <div class="swiper-slide" role="group" aria-label="Featured post 1 of 1">
                    <a href="/blog/experience-brain-orgasm/" aria-label="Read article: Can Anyone Experience Brain Orgasm?">
                        <img src="https://img.beyourlover.com/2025/03/20020642/Brain-Orgasm-banner.jpg" alt="Brain Orgasm - Benefits and Methods Explained" width="800" height="500" loading="eager" fetchpriority="high" decoding="async">
                        <div class="swiper-text">
                            <span class="swiper-txt1">
                                Can Anyone Experience "Brain Orgasm"? Benefits and Methods Explained!
                            </span>
                            <span class="swiper-txt2">
                                <p>Have You Heard of a "Brain Orgasm"? Though it may sound strange, the term "brain
                                    orgasm" isn't as out there as it may seem. Simply put, a "brain orgasm" is an
                                    orgasm that was reached without the physical stimulation of the genitals.
                                    Instead, the orgasm is achieved through methods like imagination, sounds, or
                                    visual input, […]</p>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="swiper-pagination" aria-label="Carousel pagination"></div>
        </div>
    </div>

    <!-- 右侧分类 -->
    <div class="hobl-right">
        <div class="hobl-class" role="list" aria-label="Blog categories list">
            <div class="hobl-item" role="listitem">
                <a href="/sex-toy-guides/" aria-label="Browse Sex Toy Guides category">
                    <img src="https://img.beyourlover.com/2025/03/20020817/blog-class-img01.webp" alt="Sex Toy Guides - Find the perfect pleasure companion" width="200" height="200" loading="lazy" decoding="async">
                    <div class="hobl-txt">
                        <span class="hobl-txt1">SEX TOY GUIDES</span>
                        <span class="hobl-txt2">Find the perfect pleasure companion</span>
                    </div>
                </a>
            </div>
            <div class="hobl-item" role="listitem">
                <a href="/sex-tips-advice/" aria-label="Browse Sex Tips and Advice category">
                    <img src="https://img.beyourlover.com/2025/03/20020754/blog-2-sex-tips.webp" alt="Sex Tips and Advice - Enhance intimacy with expert tips" width="200" height="200" loading="lazy" decoding="async">
                    <div class="hobl-txt">
                        <span class="hobl-txt1">SEX TIPS &amp; ADVICE</span>
                        <span class="hobl-txt2">Enhance intimacy with expert tips</span>
                    </div>
                </a>
            </div>
            <div class="hobl-item" role="listitem">
                <a href="/sex-educations/" aria-label="Browse Sexual Wellness category">
                    <img src="https://img.beyourlover.com/2025/03/20020811/blog-class-img03.webp" alt="Sexual Wellness - Prioritize pleasure and self-care" width="200" height="200" loading="lazy" decoding="async">
                    <div class="hobl-txt">
                        <span class="hobl-txt1">SEXUAL WELLNESS</span>
                        <span class="hobl-txt2">Prioritize pleasure and self-care</span>
                    </div>
                </a>
            </div>
            <div class="hobl-item" role="listitem">
                <a href="/sex-cartoon/" aria-label="Browse Sex Cartoon category">
                    <img src="https://img.beyourlover.com/2025/03/20020801/blog-class-img04.webp" alt="Sex Cartoon - Fun, flirty, and cheeky illustrations" width="200" height="200" loading="lazy" decoding="async">
                    <div class="hobl-txt">
                        <span class="hobl-txt1">SEX CARTOON</span>
                        <span class="hobl-txt2">Fun, flirty, and cheeky illustrations</span>
                    </div>
                </a>
            </div>
            <div class="hobl-item" role="listitem">
                <a href="/relationships/" aria-label="Browse Relationships category">
                    <img src="https://img.beyourlover.com/2025/03/20020825/blog-class-img05.webp" alt="Relationships - Navigate love, sex, and connection" width="200" height="200" loading="lazy" decoding="async">
                    <div class="hobl-txt">
                        <span class="hobl-txt1">RELATIONSHIPS</span>
                        <span class="hobl-txt2">Navigate love, sex, and connection</span>
                    </div>
                </a>
            </div>
            <div class="hobl-item" role="listitem">
                <a href="/news/" aria-label="Browse News category">
                    <img src="https://img.beyourlover.com/2025/03/20020833/blog-class-img06.webp" alt="News - Stay updated on all things pleasure" width="200" height="200" loading="lazy" decoding="async">
                    <div class="hobl-txt">
                        <span class="hobl-txt1">NEWS</span>
                        <span class="hobl-txt2">Stay updated on all things pleasure</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
<div class="endorsement-module">
    <div class="home-module-title">WE ARE RECOMMENDED BY</div>
    <div class="carousel-namespace">
        <div class="carousel-stage">
            <div class="belt-track" id="beltTrack">
                <div class="belt-scroll" id="beltScroll"><div class="belt-group">
                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091206/home-brand01.png" alt="BeYourLover 品牌标志 - 时尚服饰品牌" title="品牌风尚" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091209/home-brand02.png" alt="BeYourLover 优雅系列品牌标志" title="优雅系列" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091212/home-brand03.png" alt="BeYourLover 经典格调品牌标志" title="经典格调" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091215/home-brand04.png" alt="BeYourLover 轻奢生活品牌标志" title="轻奢生活" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091219/home-brand05.png" alt="BeYourLover 都市摩登品牌标志" title="都市摩登" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/18062808/home-brand10.png" alt="BeYourLover 自然之美品牌标志" title="自然之美" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091228/home-brand07.png" alt="BeYourLover 艺术灵感品牌标志" title="艺术灵感" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091232/home-brand08.png" alt="BeYourLover 极简主义品牌标志" title="极简主义" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091256/home-brand09.png" alt="BeYourLover 未来先锋品牌标志" title="未来先锋" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/18062813/home-brand11.png" alt="BeYourLover 未来先锋品牌标志" title="未来先锋" loading="lazy" decoding="async">
                    </div>
                </div>
            </div><div class="belt-group">
                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091206/home-brand01.png" alt="BeYourLover 品牌标志 - 时尚服饰品牌" title="品牌风尚" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091209/home-brand02.png" alt="BeYourLover 优雅系列品牌标志" title="优雅系列" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091212/home-brand03.png" alt="BeYourLover 经典格调品牌标志" title="经典格调" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091215/home-brand04.png" alt="BeYourLover 轻奢生活品牌标志" title="轻奢生活" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091219/home-brand05.png" alt="BeYourLover 都市摩登品牌标志" title="都市摩登" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/18062808/home-brand10.png" alt="BeYourLover 自然之美品牌标志" title="自然之美" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091228/home-brand07.png" alt="BeYourLover 艺术灵感品牌标志" title="艺术灵感" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091232/home-brand08.png" alt="BeYourLover 极简主义品牌标志" title="极简主义" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091256/home-brand09.png" alt="BeYourLover 未来先锋品牌标志" title="未来先锋" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/18062813/home-brand11.png" alt="BeYourLover 未来先锋品牌标志" title="未来先锋" loading="lazy" decoding="async">
                    </div>
                </div>
            </div><div class="belt-group">
                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091206/home-brand01.png" alt="BeYourLover 品牌标志 - 时尚服饰品牌" title="品牌风尚" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091209/home-brand02.png" alt="BeYourLover 优雅系列品牌标志" title="优雅系列" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091212/home-brand03.png" alt="BeYourLover 经典格调品牌标志" title="经典格调" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091215/home-brand04.png" alt="BeYourLover 轻奢生活品牌标志" title="轻奢生活" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091219/home-brand05.png" alt="BeYourLover 都市摩登品牌标志" title="都市摩登" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/18062808/home-brand10.png" alt="BeYourLover 自然之美品牌标志" title="自然之美" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091228/home-brand07.png" alt="BeYourLover 艺术灵感品牌标志" title="艺术灵感" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091232/home-brand08.png" alt="BeYourLover 极简主义品牌标志" title="极简主义" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/17091256/home-brand09.png" alt="BeYourLover 未来先锋品牌标志" title="未来先锋" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="icon-item">
                    <div class="icon-svg">
                        <img src="https://img.beyourlover.com/2026/04/18062813/home-brand11.png" alt="BeYourLover 未来先锋品牌标志" title="未来先锋" loading="lazy" decoding="async">
                    </div>
                </div>
            </div></div>
            </div>
        </div>
    </div>
</div>
</div>
</main>
<div class="byl-www-lower byl-www-footer-wrapper" data-www-source="2026-10-14">
<footer id="colophon" class="site-footer">
		<div class="global-footer-container">
<!-- 底部卖点, ./asset/css/custom-footer.css -->
	<div class="footer-model04 col-full">
		<div class="guarantee-list">
			<div class="guarantee-item">
				<img src="https://www.beyourlover.com/wp-content/uploads/2025/02/payment-security02.svg" alt="guarantee-icon">
				<div class="txt1">
					Secure Checkou
				</div>
			</div>
			<div class="guarantee-item">
				<img src="https://www.beyourlover.com/wp-content/uploads/2025/02/discreet-shipping.svg" alt="guarantee-icon">
				<div class="txt1">
					Free Shipping
				</div>
			</div>
			<div class="guarantee-item">
				<img src="https://www.beyourlover.com/wp-content/uploads/2025/02/Beyourlover-toy.svg" alt="guarantee-icon">
				<div class="txt1">
					Satisfaction Guaranteed
				</div>
			</div>
			<div class="guarantee-item">
				<img src="https://www.beyourlover.com/wp-content/uploads/2025/02/Do-you-need-help.svg" alt="guarantee-icon">
				<div class="txt1">
					Secure Checkou
				</div>
			</div>
		</div>
	</div>

	<div class="footer-top">
	</div>

	<div class="footer-middle col-full">
		<div class="footer-email">
			<div class="footer-email-text">
				<div class="footer-email-txt1">JOIN BEYOURLOVER CLUB</div>
				<div class="footer-email-txt2">Sign up to receive 15% off your first order and exclusive access to our best offers.</div>
			</div>
			<div class="wpforms-container byl-www-newsletter-preview"><div class="byl-www-newsletter-fields"><input type="email" placeholder="Email Address" readonly="" aria-label="Email Address"><button type="button" disabled="">SUBSCRIBE</button></div></div>  <!-- .wpforms-container -->		</div>

		<div class="footer-model04">
			<div class="footer-class-list">
				<div class="footer-class-item">
					<div class="txt1">
						About Us
					</div>
					<div class="txt2">
						BeYourLover makes intimacy giftable.
						We make playful intimate products that you'll actually want to give, receive, and keep around. Something beautiful enough to give. Playful enough to surprise. Personal enough to make that special someone blush.
						For your partner, a friend, or just yourself, we're here to make pleasure feel less awkward and a lot more giftable.
						Love is the best gift that you could ever give.&nbsp;
						We're here to help you express your love in a classy yet sensual way.
					</div>
				</div>
				<div class="footer-class-item">
					<div class="txt1">
						Our Services
					</div>
					<a href="/faqs/">FAQs</a>
					<a href="/shipping-policies/">Shipping Policies</a>
					<a href="/warranty/">Warranty</a>
					<a href="/payment-policies">Payment Policies</a>
					<a href="/return-policies">Return Policies</a>
					<a href="/track-your-order/">Track Your Order</a>
					<a href="/happiness-guarantee/">Happiness Guarantee</a>
					<a href="/wishlist/">Wishlist</a>
					<a href="/user-manuals/">User Manuals</a>
					<a href="/random-gift-policies/">Random Gift Policies</a>
				</div>
				<div class="footer-class-item">
					<div class="txt1">
						Company
					</div>
					<a href="https://www.beyourlover.com/about-us/">About Us</a>
					<a href="https://www.beyourlover.com/contact-us/">Contact Us</a>
					<a href="https://www.beyourlover.com/affiliates/">Affiliates</a>
					<a href="https://www.beyourlover.com/shop/">Shop</a>
					<a href="https://www.beyourlover.com/blog/">Blog</a>
				</div>
				<div class="footer-class-item">
					<div class="txt1">
						Contact Us
					</div>
					<div class="txt3">
						<svg aria-hidden="true" class="e-font-icon-svg e-fas-phone-volume" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
							<path d="M97.333 506.966c-129.874-129.874-129.681-340.252 0-469.933 5.698-5.698 14.527-6.632 21.263-2.422l64.817 40.513a17.187 17.187 0 0 1 6.849 20.958l-32.408 81.021a17.188 17.188 0 0 1-17.669 10.719l-55.81-5.58c-21.051 58.261-20.612 122.471 0 179.515l55.811-5.581a17.188 17.188 0 0 1 17.669 10.719l32.408 81.022a17.188 17.188 0 0 1-6.849 20.958l-64.817 40.513a17.19 17.19 0 0 1-21.264-2.422zM247.126 95.473c11.832 20.047 11.832 45.008 0 65.055-3.95 6.693-13.108 7.959-18.718 2.581l-5.975-5.726c-3.911-3.748-4.793-9.622-2.261-14.41a32.063 32.063 0 0 0 0-29.945c-2.533-4.788-1.65-10.662 2.261-14.41l5.975-5.726c5.61-5.378 14.768-4.112 18.718 2.581zm91.787-91.187c60.14 71.604 60.092 175.882 0 247.428-4.474 5.327-12.53 5.746-17.552.933l-5.798-5.557c-4.56-4.371-4.977-11.529-.93-16.379 49.687-59.538 49.646-145.933 0-205.422-4.047-4.85-3.631-12.008.93-16.379l5.798-5.557c5.022-4.813 13.078-4.394 17.552.933zm-45.972 44.941c36.05 46.322 36.108 111.149 0 157.546-4.39 5.641-12.697 6.251-17.856 1.304l-5.818-5.579c-4.4-4.219-4.998-11.095-1.285-15.931 26.536-34.564 26.534-82.572 0-117.134-3.713-4.836-3.115-11.711 1.285-15.931l5.818-5.579c5.159-4.947 13.466-4.337 17.856 1.304z">
							</path>
						</svg>
						+1 6462438557
					</div>
					<div class="txt3">
						<svg aria-hidden="true" class="e-font-icon-svg e-far-envelope" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
							<path d="M464 64H48C21.49 64 0 85.49 0 112v288c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V112c0-26.51-21.49-48-48-48zm0 48v40.805c-22.422 18.259-58.168 46.651-134.587 106.49-16.841 13.247-50.201 45.072-73.413 44.701-23.208.375-56.579-31.459-73.413-44.701C106.18 199.465 70.425 171.067 48 152.805V112h416zM48 400V214.398c22.914 18.251 55.409 43.862 104.938 82.646 21.857 17.205 60.134 55.186 103.062 54.955 42.717.231 80.509-37.199 103.053-54.947 49.528-38.783 82.032-64.401 104.947-82.653V400H48z">
							</path>
						</svg>
						support@beyourlover.com
					</div>
					<div class="txt3">
						<svg aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
							<path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z">
							</path>
						</svg>
						13767 Freeway Dr, Santa Fe Springs, CA USA
					</div>
					<div class="txt3">
						<svg aria-hidden="true" class="e-font-icon-svg e-far-calendar-alt" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
							<path d="M148 288h-40c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12zm108-12v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 96v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm192 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96-260v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48zm-48 346V160H48v298c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z">
							</path>
						</svg>
						Mon.-Fri : 10-20 Sun : 12-16
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="footer-bottom col-full">
		<div class="footer-model04">
			<div class="footer-social-media">
				<div class="social-media-cont">
					<div class="soc-me-left">
						<div class="tit1">
							FOLLOW BeYourLover
						</div>
						<div class="follow-list">
							<span class="follow-item">
								<a class="follow-icon" href="https://www.facebook.com/beyourlovercom" target="_blank">
									<svg class="e-font-icon-svg e-fab-facebook" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
										<path d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z">
										</path>
									</svg>
								</a>
							</span>
							<span class="follow-item">
								<a class="follow-icon" href="https://x.com/beyourlovertoys" target="_blank">
									<svg class="e-font-icon-svg e-fab-twitter" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
										<path d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z">
										</path>
									</svg>
								</a>
							</span>
							<span class="follow-item">
								<a class="follow-icon" href="https://Instagram.com/beyourlovertoys" target="_blank">
									<svg class="e-font-icon-svg e-fab-instagram" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
										<path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z">
										</path>
									</svg>
								</a>
							</span>
							<span class="follow-item">
								<a class="follow-icon" href="https://www.reddit.com/r/beyourlovercom/" target="_blank">
									<svg class="e-font-icon-svg e-fab-reddit" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
										<path d="M201.5 305.5c-13.8 0-24.9-11.1-24.9-24.6 0-13.8 11.1-24.9 24.9-24.9 13.6 0 24.6 11.1 24.6 24.9 0 13.6-11.1 24.6-24.6 24.6zM504 256c0 137-111 248-248 248S8 393 8 256 119 8 256 8s248 111 248 248zm-132.3-41.2c-9.4 0-17.7 3.9-23.8 10-22.4-15.5-52.6-25.5-86.1-26.6l17.4-78.3 55.4 12.5c0 13.6 11.1 24.6 24.6 24.6 13.8 0 24.9-11.3 24.9-24.9s-11.1-24.9-24.9-24.9c-9.7 0-18 5.8-22.1 13.8l-61.2-13.6c-3-.8-6.1 1.4-6.9 4.4l-19.1 86.4c-33.2 1.4-63.1 11.3-85.5 26.8-6.1-6.4-14.7-10.2-24.1-10.2-34.9 0-46.3 46.9-14.4 62.8-1.1 5-1.7 10.2-1.7 15.5 0 52.6 59.2 95.2 132 95.2 73.1 0 132.3-42.6 132.3-95.2 0-5.3-.6-10.8-1.9-15.8 31.3-16 19.8-62.5-14.9-62.5zM302.8 331c-18.2 18.2-76.1 17.9-93.6 0-2.2-2.2-6.1-2.2-8.3 0-2.5 2.5-2.5 6.4 0 8.6 22.8 22.8 87.3 22.8 110.2 0 2.5-2.2 2.5-6.1 0-8.6-2.2-2.2-6.1-2.2-8.3 0zm7.7-75c-13.6 0-24.6 11.1-24.6 24.9 0 13.6 11.1 24.6 24.6 24.6 13.8 0 24.9-11.1 24.9-24.6 0-13.8-11-24.9-24.9-24.9z">
										</path>
									</svg>
								</a>
							</span>
							<span class="follow-item">
								<a class="follow-icon" href="https://www.tiktok.com/@beyourlover_planet" target="_blank">
									<svg class="e-font-icon-svg e-fab-tiktok" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
										<path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z">
										</path>
									</svg>
								</a>
							</span>
						</div>
					</div>
					<div class="soc-me-right">
						<div class="tit1">
							APP DOWNLOAD
						</div>
						<div class="appStoreBadges__column">
							<a data-v-cfed1502="" href="https://download.beyourlover.com" class="footer__download">
								<div data-v-cfed1502="" class="footer__download-logo">
									<img data-v-cfed1502="" alt="google-play" class="showImg" data-src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAApCAMAAABwfJv6AAAAflBMVEUAAAA6xfJowmrddFBmv2n91AXxNk3xNk3vNU3wNk1nwGpnwGpmwGr/2QbxOE//1wX81Qf91Ab81AZpwmzxN0zvP1WVxUtardf81gZNxL3zO0761QU7xfNmvmnvNk380wZWsNvaR2FRwa3kP1dLwr33lCKNxU5nospUwaB1uoPvfAnaAAAAHHRSTlMA3yAR5eS9ge/gvJpwVEsfupR5Uzwo38GmpWg3phqe3gAAANxJREFUOMvN09kSwUAUBFAMkiCrnSyCWP7/B3XJ0A9TWt7o51MzXXfu9H4e09Fti8mgE+yfi2JKKmAOOZx1gZCg8++wlbaqglayqoD5EU5XBaRUVQEpZVVASlYVkBJVBaREVqGClJeqSmIFIa1D0ljA/PZyyCgTEPLpLA0dyNwBKD/D66mpmNSBdGXZODe78AD3lpmhIqSzchM7c6SrgVqZ7CkIeZ5NEIm3pvM9tT281zNiH+nGS7HhdOOF+DN0wU7/Qut8lNMQjuUEhGM5BeE4YJF1zQHLmMj0/jcPcmk7awZnqVEAAAAASUVORK5CYII=" data-srcset="" srcset="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAApCAMAAABwfJv6AAAAflBMVEUAAAA6xfJowmrddFBmv2n91AXxNk3xNk3vNU3wNk1nwGpnwGpmwGr/2QbxOE//1wX81Qf91Ab81AZpwmzxN0zvP1WVxUtardf81gZNxL3zO0761QU7xfNmvmnvNk380wZWsNvaR2FRwa3kP1dLwr33lCKNxU5nospUwaB1uoPvfAnaAAAAHHRSTlMA3yAR5eS9ge/gvJpwVEsfupR5Uzwo38GmpWg3phqe3gAAANxJREFUOMvN09kSwUAUBFAMkiCrnSyCWP7/B3XJ0A9TWt7o51MzXXfu9H4e09Fti8mgE+yfi2JKKmAOOZx1gZCg8++wlbaqglayqoD5EU5XBaRUVQEpZVVASlYVkBJVBaREVqGClJeqSmIFIa1D0ljA/PZyyCgTEPLpLA0dyNwBKD/D66mpmNSBdGXZODe78AD3lpmhIqSzchM7c6SrgVqZ7CkIeZ5NEIm3pvM9tT281zNiH+nGS7HhdOOF+DN0wU7/Qut8lNMQjuUEhGM5BeE4YJF1zQHLmMj0/jcPcmk7awZnqVEAAAAASUVORK5CYII=">
								</div>
								<div data-v-cfed1502="" class="footer__download-title">GET IT ON</div>
								<div data-v-cfed1502="" class="footer__download-name">Google Play</div>
							</a>
							<a data-v-cfed1502="" href="https://download.beyourlover.com" class="footer__download">
								<div data-v-cfed1502="" class="footer__download-logo">
									<img data-v-cfed1502="" alt="huawei-app-gallery" class="showImg" data-src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAMAAAC7IEhfAAAAP1BMVEUAAAD//v7//v7//v7//////f3//////v7//v7//v7//////////////////v7//v7//f3//////////////v6wgQJ3AAAAFHRSTlMA7YTQEZUZ2qrEc1xHNbyzoi0kUrJIRvIAAADTSURBVDjLtZRREoMgDEQJiAiooO79z9oy9idoxsy0fd9vguwSzddsPhwazwOYFd6KN6NuHtyzt6Ch+MTavGKeIQBxUIgAWaPB5jZOHDnaSOTsZpqUCwFU76JMhBNn00T4EPfem3APbToPiMzLkHC8SAeBwO8+Q2LpgoNAubwqgXwpV2DXisPPJ7auVTF6ZdGgkYsJEtNlm5SHE0TWnXcoE9ifBjJ8zwqUF1+gjbJKA/u6B5IeWs+sCFyux7GDuzBDtX4ltoI9h6Pgz3UaU6RgzT95AXPWNRd2d2UiAAAAAElFTkSuQmCC" data-srcset="" srcset="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAMAAAC7IEhfAAAAP1BMVEUAAAD//v7//v7//v7//////f3//////v7//v7//v7//////////////////v7//v7//f3//////////////v6wgQJ3AAAAFHRSTlMA7YTQEZUZ2qrEc1xHNbyzoi0kUrJIRvIAAADTSURBVDjLtZRREoMgDEQJiAiooO79z9oy9idoxsy0fd9vguwSzddsPhwazwOYFd6KN6NuHtyzt6Ch+MTavGKeIQBxUIgAWaPB5jZOHDnaSOTsZpqUCwFU76JMhBNn00T4EPfem3APbToPiMzLkHC8SAeBwO8+Q2LpgoNAubwqgXwpV2DXisPPJ7auVTF6ZdGgkYsJEtNlm5SHE0TWnXcoE9ifBjJ8zwqUF1+gjbJKA/u6B5IeWs+sCFyux7GDuzBDtX4ltoI9h6Pgz3UaU6RgzT95AXPWNRd2d2UiAAAAAElFTkSuQmCC">
								</div>
								<div data-v-cfed1502="" class="footer__download-title">GET IT ON</div>
								<div data-v-cfed1502="" class="footer__download-name">App Store</div>
							</a>
						</div>
					</div>

				</div>
			</div>
			<div class="footer__copyright">
				<ul class="policies">
					<li><a href="/privacy-policies" rel="nofollow">Privacy Policy</a></li>
					<li><a href="/terms-and-conditions" rel="nofollow">T&amp;C</a></li>
					<li><a href="/cookies-policies" rel="nofollow">Cookie Policy</a></li>
				</ul>
				<span class="copyright-text">
					<br>© 2025 BeYourLover <br>
					All models appearing on this website are 18 years or older. By entering this site, you swear that you are of
					legal age in your area to view adult material and that you wish to view such material.
				</span>
			</div>

			<div class="footer-seo copyright-text">
				<div class="so-accordion-wrapper">
					<div class="so-tab">
						<input name="tabs" type="checkbox" id="so-tab-1">
						<label for="so-tab-1">BeYourLover | Award Winning Sex Toys | High-Quality &amp; Innovative
							Products</label>
						<div class="so-tab-content">
							<div class="module4-cont">
								<div class="home-module-title">
									ABOUT BEYOURLOVER
								</div>
								<div class="module4-txt2">
									BeYourLover is committed to creating a new, life-affirming gift mall that resonates with people. We spread love and beauty with our unique, lovely toys, offering personalized services and a diverse range of products for those seeking new excitement and unique experiences.
								</div>
								<div class="module4-txt2">
									BeYourLover offers a wide range of discreet luxury adult toys for both men and women. With
									innovative &amp; thoughtful designs, our products are sure to give you the high-quality enjoymen
									that you deserve.
									As a global award winning adult toys brand, BeYourLover is aims to provide a premium sexual
									lifestyle for you with high quality &amp; innovative products.
								</div>
							</div>

							<h2 data-v-cfed1502=""></h2>
							<div id="seo-internal-link">
								<div data-v-c3cefe32="" data-v-dae3f2bd="" class="blu-accordion__content b-overflow-y">
									<section data-v-c3cefe32="" class="blu-accordion__content-main">
										<h2 data-v-dae3f2bd="">BeYourLover Sex Toys</h2>
										<p data-v-dae3f2bd="">
										</p>
										<h2><strong>What can you get from BeYourLover?</strong></h2>
										<p>
											Best sex toys from BeYourLover.
										</p>
										<h2>What's on BeYourLover?</h2>
										<p>

										</p>
										<ul>
											<li>Worldwide discreet shipping.</li>
										</ul>
										<h2>Most Popular product on BeYourLover</h2>
										<p>
											<a href="/beyourlover-clock-alarm/">Naughty Clock</a> |
											<a href="/beyourlover-fatima-green/">BeYourLover
												Fatima</a>
										</p>
										<h3>Product Category</h3>
										<p>
											<a href="/sex-toys-for-women/">Sex toys for
												women</a>&nbsp;|&nbsp;<a href="/sex-toys-for-men/">Sex toys for
												men</a>&nbsp;|&nbsp;<a href="/sex-toys-for-couples/">Sex toys for
												couples</a>
										</p>
										<h3>Vibrators</h3>
										<p>Vibrators for women <a href="/vibrators/">Vibrators</a>
										</p>
										<h3>Male Masturbation</h3>
										<div id="seo-internal-link">
											<div class="seo-link-container">
												<div class="seo-link-header">
													<h3><a href="/blog/">Popular Blog</a></h3>
												</div>
												<div class="seo-link-items">
													<span class="tag-label">LOVER'S JOURNAL:</span>
													<a href="/blog/how-to-hump-a-pillow/">How to hump a pillow</a>
													<a href="/blog/beyourlover-launches-adult-clock-alarm/">Adult clock alarm lunched</a>
													<a href="/blog/how-to-give-cunnilingus/">How to give cunnilingus</a>
													<a href="/blog/superman-sex-position/">Superman Sex position</a>
													<a href="https://idollmate.com/" target="_blank">Idollmate</a>
												</div>
											</div>

											<div class="seo-link-container">
												<div class="seo-link-header">
													<h3><a href="/promos">Latest BeYourLover Promos</a></h3>
												</div>
												<div class="seo-link-items">
													<div class="seo-link-item"><span class="tag-label">Tags:</span></div>
													<div class="seo-link-item"><a href="/discounts-and-deals/">Promo Spring 2026</a></div>
													<div class="seo-link-item"><div class="seo-divider"></div></div>
												</div>
											</div>
										</div>
									</section>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="footer-payment">
			<div class="footer-payment-left">
				<span class="byl-www-static-currency">$ USD</span>			</div>


			<div class="footer-language-trust">
				<!-- 其他支付图标 -->
				<div class="footer-trust">
					<ul class="inline-list payment-icons site-footer__bottom-block text-center">
						<li class="icon--payment">
							<svg xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="pi-american_express" viewBox="0 0 38 24" width="38" height="24">
								<title id="pi-american_express">American Express</title>
								<path fill="#000" d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3Z" opacity=".07"></path>
								<path fill="#006FCF" d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32Z">
								</path>
								<path fill="#FFF" d="M22.012 19.936v-8.421L37 11.528v2.326l-1.732 1.852L37 17.573v2.375h-2.766l-1.47-1.622-1.46 1.628-9.292-.02Z">
								</path>
								<path fill="#006FCF" d="M23.013 19.012v-6.57h5.572v1.513h-3.768v1.028h3.678v1.488h-3.678v1.01h3.768v1.531h-5.572Z">
								</path>
								<path fill="#006FCF" d="m28.557 19.012 3.083-3.289-3.083-3.282h2.386l1.884 2.083 1.89-2.082H37v.051l-3.017 3.23L37 18.92v.093h-2.307l-1.917-2.103-1.898 2.104h-2.321Z">
								</path>
								<path fill="#FFF" d="M22.71 4.04h3.614l1.269 2.881V4.04h4.46l.77 2.159.771-2.159H37v8.421H19l3.71-8.421Z"></path>
								<path fill="#006FCF" d="m23.395 4.955-2.916 6.566h2l.55-1.315h2.98l.55 1.315h2.05l-2.904-6.566h-2.31Zm.25 3.777.875-2.09.873 2.09h-1.748Z">
								</path>
								<path fill="#006FCF" d="M28.581 11.52V4.953l2.811.01L32.84 9l1.456-4.046H37v6.565l-1.74.016v-4.51l-1.644 4.494h-1.59L30.35 7.01v4.51h-1.768Z">
								</path>
							</svg>
						</li>
						<li class="icon--payment">
							<svg version="1.1" xmlns="http://www.w3.org/2000/svg" role="img" x="0" y="0" width="38" height="24" viewBox="0 0 165.521 105.965" xml:space="preserve" aria-labelledby="pi-apple_pay">
								<title id="pi-apple_pay">Apple Pay</title>
								<path fill="#000" d="M150.698 0H14.823c-.566 0-1.133 0-1.698.003-.477.004-.953.009-1.43.022-1.039.028-2.087.09-3.113.274a10.51 10.51 0 0 0-2.958.975 9.932 9.932 0 0 0-4.35 4.35 10.463 10.463 0 0 0-.975 2.96C.113 9.611.052 10.658.024 11.696a70.22 70.22 0 0 0-.022 1.43C0 13.69 0 14.256 0 14.823v76.318c0 .567 0 1.132.002 1.699.003.476.009.953.022 1.43.028 1.036.09 2.084.275 3.11a10.46 10.46 0 0 0 .974 2.96 9.897 9.897 0 0 0 1.83 2.52 9.874 9.874 0 0 0 2.52 1.83c.947.483 1.917.79 2.96.977 1.025.183 2.073.245 3.112.273.477.011.953.017 1.43.02.565.004 1.132.004 1.698.004h135.875c.565 0 1.132 0 1.697-.004.476-.002.952-.009 1.431-.02 1.037-.028 2.085-.09 3.113-.273a10.478 10.478 0 0 0 2.958-.977 9.955 9.955 0 0 0 4.35-4.35c.483-.947.789-1.917.974-2.96.186-1.026.246-2.074.274-3.11.013-.477.02-.954.022-1.43.004-.567.004-1.132.004-1.699V14.824c0-.567 0-1.133-.004-1.699a63.067 63.067 0 0 0-.022-1.429c-.028-1.038-.088-2.085-.274-3.112a10.4 10.4 0 0 0-.974-2.96 9.94 9.94 0 0 0-4.35-4.35A10.52 10.52 0 0 0 156.939.3c-1.028-.185-2.076-.246-3.113-.274a71.417 71.417 0 0 0-1.431-.022C151.83 0 151.263 0 150.698 0z">
								</path>
								<path fill="#FFF" d="M150.698 3.532l1.672.003c.452.003.905.008 1.36.02.793.022 1.719.065 2.583.22.75.135 1.38.34 1.984.648a6.392 6.392 0 0 1 2.804 2.807c.306.6.51 1.226.645 1.983.154.854.197 1.783.218 2.58.013.45.019.9.02 1.36.005.557.005 1.113.005 1.671v76.318c0 .558 0 1.114-.004 1.682-.002.45-.008.9-.02 1.35-.022.796-.065 1.725-.221 2.589a6.855 6.855 0 0 1-.645 1.975 6.397 6.397 0 0 1-2.808 2.807c-.6.306-1.228.511-1.971.645-.881.157-1.847.2-2.574.22-.457.01-.912.017-1.379.019-.555.004-1.113.004-1.669.004H14.801c-.55 0-1.1 0-1.66-.004a74.993 74.993 0 0 1-1.35-.018c-.744-.02-1.71-.064-2.584-.22a6.938 6.938 0 0 1-1.986-.65 6.337 6.337 0 0 1-1.622-1.18 6.355 6.355 0 0 1-1.178-1.623 6.935 6.935 0 0 1-.646-1.985c-.156-.863-.2-1.788-.22-2.578a66.088 66.088 0 0 1-.02-1.355l-.003-1.327V14.474l.002-1.325a66.7 66.7 0 0 1 .02-1.357c.022-.792.065-1.717.222-2.587a6.924 6.924 0 0 1 .646-1.981c.304-.598.7-1.144 1.18-1.623a6.386 6.386 0 0 1 1.624-1.18 6.96 6.96 0 0 1 1.98-.646c.865-.155 1.792-.198 2.586-.22.452-.012.905-.017 1.354-.02l1.677-.003h135.875">
								</path>
								<g>
									<g>
										<path fill="#000" d="M43.508 35.77c1.404-1.755 2.356-4.112 2.105-6.52-2.054.102-4.56 1.355-6.012 3.112-1.303 1.504-2.456 3.959-2.156 6.266 2.306.2 4.61-1.152 6.063-2.858">
										</path>
										<path fill="#000" d="M45.587 39.079c-3.35-.2-6.196 1.9-7.795 1.9-1.6 0-4.049-1.8-6.698-1.751-3.447.05-6.645 2-8.395 5.1-3.598 6.2-.95 15.4 2.55 20.45 1.699 2.5 3.747 5.25 6.445 5.151 2.55-.1 3.549-1.65 6.647-1.65 3.097 0 3.997 1.65 6.696 1.6 2.798-.05 4.548-2.5 6.247-5 1.95-2.85 2.747-5.6 2.797-5.75-.05-.05-5.396-2.101-5.446-8.251-.05-5.15 4.198-7.6 4.398-7.751-2.399-3.548-6.147-3.948-7.447-4.048">
										</path>
									</g>
									<g>
										<path fill="#000" d="M78.973 32.11c7.278 0 12.347 5.017 12.347 12.321 0 7.33-5.173 12.373-12.529 12.373h-8.058V69.62h-5.822V32.11h14.062zm-8.24 19.807h6.68c5.07 0 7.954-2.729 7.954-7.46 0-4.73-2.885-7.434-7.928-7.434h-6.706v14.894z">
										</path>
										<path fill="#000" d="M92.764 61.847c0-4.809 3.665-7.564 10.423-7.98l7.252-.442v-2.08c0-3.04-2.001-4.704-5.562-4.704-2.938 0-5.07 1.507-5.51 3.82h-5.252c.157-4.86 4.731-8.395 10.918-8.395 6.654 0 10.995 3.483 10.995 8.89v18.663h-5.38v-4.497h-.13c-1.534 2.937-4.914 4.782-8.579 4.782-5.406 0-9.175-3.222-9.175-8.057zm17.675-2.417v-2.106l-6.472.416c-3.64.234-5.536 1.585-5.536 3.95 0 2.288 1.975 3.77 5.068 3.77 3.95 0 6.94-2.522 6.94-6.03z">
										</path>
										<path fill="#000" d="M120.975 79.652v-4.496c.364.051 1.247.103 1.715.103 2.573 0 4.029-1.09 4.913-3.899l.52-1.663-9.852-27.293h6.082l6.863 22.146h.13l6.862-22.146h5.927l-10.216 28.67c-2.34 6.577-5.017 8.735-10.683 8.735-.442 0-1.872-.052-2.261-.157z">
										</path>
									</g>
								</g>
							</svg>
						</li>
						<li class="icon--payment">
							<svg xmlns="http://www.w3.org/2000/svg" role="img" viewBox="0 0 38 24" width="38" height="24" aria-labelledby="pi-google_pay">
								<title id="pi-google_pay">Google Pay</title>
								<path d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3z" fill="#000" opacity=".07"></path>
								<path d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32" fill="#FFF">
								</path>
								<path d="M18.093 11.976v3.2h-1.018v-7.9h2.691a2.447 2.447 0 0 1 1.747.692 2.28 2.28 0 0 1 .11 3.224l-.11.116c-.47.447-1.098.69-1.747.674l-1.673-.006zm0-3.732v2.788h1.698c.377.012.741-.135 1.005-.404a1.391 1.391 0 0 0-1.005-2.354l-1.698-.03zm6.484 1.348c.65-.03 1.286.188 1.778.613.445.43.682 1.03.65 1.649v3.334h-.969v-.766h-.049a1.93 1.93 0 0 1-1.673.931 2.17 2.17 0 0 1-1.496-.533 1.667 1.667 0 0 1-.613-1.324 1.606 1.606 0 0 1 .613-1.336 2.746 2.746 0 0 1 1.698-.515c.517-.02 1.03.093 1.49.331v-.208a1.134 1.134 0 0 0-.417-.901 1.416 1.416 0 0 0-.98-.368 1.545 1.545 0 0 0-1.319.717l-.895-.564a2.488 2.488 0 0 1 2.182-1.06zM23.29 13.52a.79.79 0 0 0 .337.662c.223.176.5.269.785.263.429-.001.84-.17 1.146-.472.305-.286.478-.685.478-1.103a2.047 2.047 0 0 0-1.324-.374 1.716 1.716 0 0 0-1.03.294.883.883 0 0 0-.392.73zm9.286-3.75l-3.39 7.79h-1.048l1.281-2.728-2.224-5.062h1.103l1.612 3.885 1.569-3.885h1.097z" fill="#5F6368"></path>
								<path d="M13.986 11.284c0-.308-.024-.616-.073-.92h-4.29v1.747h2.451a2.096 2.096 0 0 1-.9 1.373v1.134h1.464a4.433 4.433 0 0 0 1.348-3.334z" fill="#4285F4"></path>
								<path d="M9.629 15.721a4.352 4.352 0 0 0 3.01-1.097l-1.466-1.14a2.752 2.752 0 0 1-4.094-1.44H5.577v1.17a4.53 4.53 0 0 0 4.052 2.507z" fill="#34A853"></path>
								<path d="M7.079 12.05a2.709 2.709 0 0 1 0-1.735v-1.17H5.577a4.505 4.505 0 0 0 0 4.075l1.502-1.17z" fill="#FBBC04"></path>
								<path d="M9.629 8.44a2.452 2.452 0 0 1 1.74.68l1.3-1.293a4.37 4.37 0 0 0-3.065-1.183 4.53 4.53 0 0 0-4.027 2.5l1.502 1.171a2.715 2.715 0 0 1 2.55-1.875z" fill="#EA4335"></path>
							</svg>
						</li>
						<li class="icon--payment">
							<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg" role="img" width="38" height="24" aria-labelledby="pi-master">
								<title id="pi-master">Mastercard</title>
								<path opacity=".07" d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3z"></path>
								<path fill="#fff" d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32">
								</path>
								<circle fill="#EB001B" cx="15" cy="12" r="7"></circle>
								<circle fill="#F79E1B" cx="23" cy="12" r="7"></circle>
								<path fill="#FF5F00" d="M22 12c0-2.4-1.2-4.5-3-5.7-1.8 1.3-3 3.4-3 5.7s1.2 4.5 3 5.7c1.8-1.2 3-3.3 3-5.7z"></path>
							</svg>
						</li>
						<li class="icon--payment">
							<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg" width="38" height="24" role="img" aria-labelledby="pi-paypal">
								<title id="pi-paypal">PayPal</title>
								<path opacity=".07" d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3z"></path>
								<path fill="#fff" d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32">
								</path>
								<path fill="#003087" d="M23.9 8.3c.2-1 0-1.7-.6-2.3-.6-.7-1.7-1-3.1-1h-4.1c-.3 0-.5.2-.6.5L14 15.6c0 .2.1.4.3.4H17l.4-3.4 1.8-2.2 4.7-2.1z">
								</path>
								<path fill="#3086C8" d="M23.9 8.3l-.2.2c-.5 2.8-2.2 3.8-4.6 3.8H18c-.3 0-.5.2-.6.5l-.6 3.9-.2 1c0 .2.1.4.3.4H19c.3 0 .5-.2.5-.4v-.1l.4-2.4v-.1c0-.2.3-.4.5-.4h.3c2.1 0 3.7-.8 4.1-3.2.2-1 .1-1.8-.4-2.4-.1-.5-.3-.7-.5-.8z">
								</path>
								<path fill="#012169" d="M23.3 8.1c-.1-.1-.2-.1-.3-.1-.1 0-.2 0-.3-.1-.3-.1-.7-.1-1.1-.1h-3c-.1 0-.2 0-.2.1-.2.1-.3.2-.3.4l-.7 4.4v.1c0-.3.3-.5.6-.5h1.3c2.5 0 4.1-1 4.6-3.8v-.2c-.1-.1-.3-.2-.5-.2h-.1z">
								</path>
							</svg>
						</li>
						<li class="icon--payment">
							<svg viewBox="-36 25 38 24" xmlns="http://www.w3.org/2000/svg" width="38" height="24" role="img" aria-labelledby="pi-unionpay">
								<title id="pi-unionpay">Union Pay</title>
								<path fill="#005B9A" d="M-36 46.8v.7-.7zM-18.3 25v24h-7.2c-1.3 0-2.1-1-1.8-2.3l4.4-19.4c.3-1.3 1.9-2.3 3.2-2.3h1.4zm12.6 0c-1.3 0-2.9 1-3.2 2.3l-4.5 19.4c-.3 1.3.5 2.3 1.8 2.3h-4.9V25h10.8z">
								</path>
								<path fill="#E9292D" d="M-19.7 25c-1.3 0-2.9 1.1-3.2 2.3l-4.4 19.4c-.3 1.3.5 2.3 1.8 2.3h-8.9c-.8 0-1.5-.6-1.5-1.4v-21c0-.8.7-1.6 1.5-1.6h14.7z">
								</path>
								<path fill="#0E73B9" d="M-5.7 25c-1.3 0-2.9 1.1-3.2 2.3l-4.4 19.4c-.3 1.3.5 2.3 1.8 2.3H-26h.5c-1.3 0-2.1-1-1.8-2.3l4.4-19.4c.3-1.3 1.9-2.3 3.2-2.3h14z">
								</path>
								<path fill="#059DA4" d="M2 26.6v21c0 .8-.6 1.4-1.5 1.4h-12.1c-1.3 0-2.1-1.1-1.8-2.3l4.5-19.4C-8.6 26-7 25-5.7 25H.5c.9 0 1.5.7 1.5 1.6z">
								</path>
								<path fill="#fff" d="M-21.122 38.645h.14c.14 0 .28-.07.28-.14l.42-.63h1.19l-.21.35h1.4l-.21.63h-1.68c-.21.28-.42.42-.7.42h-.84l.21-.63m-.21.91h3.01l-.21.7h-1.19l-.21.7h1.19l-.21.7h-1.19l-.28 1.05c-.07.14 0 .28.28.21h.98l-.21.7h-1.89c-.35 0-.49-.21-.35-.63l.35-1.33h-.77l.21-.7h.77l.21-.7h-.7l.21-.7zm4.83-1.75v.42s.56-.42 1.12-.42h1.96l-.77 2.66c-.07.28-.35.49-.77.49h-2.24l-.49 1.89c0 .07 0 .14.14.14h.42l-.14.56h-1.12c-.42 0-.56-.14-.49-.35l1.47-5.39h.91zm1.68.77h-1.75l-.21.7s.28-.21.77-.21h1.05l.14-.49zm-.63 1.68c.14 0 .21 0 .21-.14l.14-.35h-1.75l-.14.56 1.54-.07zm-1.19.84h.98v.42h.28c.14 0 .21-.07.21-.14l.07-.28h.84l-.14.49c-.07.35-.35.49-.77.56h-.56v.77c0 .14.07.21.35.21h.49l-.14.56h-1.19c-.35 0-.49-.14-.49-.49l.07-2.1zm4.2-2.45l.21-.84h1.19l-.07.28s.56-.28 1.05-.28h1.47l-.21.84h-.21l-1.12 3.85h.21l-.21.77h-.21l-.07.35h-1.19l.07-.35h-2.17l.21-.77h.21l1.12-3.85h-.28m1.26 0l-.28 1.05s.49-.21.91-.28c.07-.35.21-.77.21-.77h-.84zm-.49 1.54l-.28 1.12s.56-.28.98-.28c.14-.42.21-.77.21-.77l-.91-.07zm.21 2.31l.21-.77h-.84l-.21.77h.84zm2.87-4.69h1.12l.07.42c0 .07.07.14.21.14h.21l-.21.7h-.77c-.28 0-.49-.07-.49-.35l-.14-.91zm-.35 1.47h3.57l-.21.77h-1.19l-.21.7h1.12l-.21.77h-1.26l-.28.42h.63l.14.84c0 .07.07.14.21.14h.21l-.21.7h-.7c-.35 0-.56-.07-.56-.35l-.14-.77-.56.84c-.14.21-.35.35-.63.35h-1.05l.21-.7h.35c.14 0 .21-.07.35-.21l.84-1.26h-1.05l.21-.77h1.19l.21-.7h-1.19l.21-.77zm-19.74-5.04c-.14.7-.42 1.19-.91 1.54-.49.35-1.12.56-1.89.56-.7 0-1.26-.21-1.54-.56-.21-.28-.35-.56-.35-.98 0-.14 0-.35.07-.56l.84-3.92h1.19l-.77 3.92v.28c0 .21.07.35.14.49.14.21.35.28.7.28s.7-.07.91-.28c.21-.21.42-.42.49-.77l.77-3.92h1.19l-.84 3.92m1.12-1.54h.84l-.07.49.14-.14c.28-.28.63-.42 1.05-.42.35 0 .63.14.77.35.14.21.21.49.14.91l-.49 2.38h-.91l.42-2.17c.07-.28.07-.49 0-.56-.07-.14-.21-.14-.35-.14-.21 0-.42.07-.56.21-.14.14-.28.35-.28.63l-.42 2.03h-.91l.63-3.57m9.8 0h.84l-.07.49.14-.14c.28-.28.63-.42 1.05-.42.35 0 .63.14.77.35s.21.49.14.91l-.49 2.38h-.91l.42-2.24c.07-.21 0-.42-.07-.49-.07-.14-.21-.14-.35-.14-.21 0-.42.07-.56.21-.14.14-.28.35-.28.63l-.42 2.03h-.91l.7-3.57m-5.81 0h.98l-.77 3.5h-.98l.77-3.5m.35-1.33h.98l-.21.84h-.98l.21-.84zm1.4 4.55c-.21-.21-.35-.56-.35-.98v-.21c0-.07 0-.21.07-.28.14-.56.35-1.05.7-1.33.35-.35.84-.49 1.33-.49.42 0 .77.14 1.05.35.21.21.35.56.35.98v.21c0 .07 0 .21-.07.28-.14.56-.35.98-.7 1.33-.35.35-.84.49-1.33.49-.35 0-.7-.14-1.05-.35m1.89-.7c.14-.21.28-.49.35-.84v-.35c0-.21-.07-.35-.14-.49a.635.635 0 0 0-.49-.21c-.28 0-.49.07-.63.28-.14.21-.28.49-.35.84v.28c0 .21.07.35.14.49.14.14.28.21.49.21.28.07.42 0 .63-.21m6.51-4.69h2.52c.49 0 .84.14 1.12.35.28.21.35.56.35.91v.28c0 .07 0 .21-.07.28-.07.49-.35.98-.7 1.26-.42.35-.84.49-1.4.49h-1.4l-.42 2.03h-1.19l1.19-5.6m.56 2.59h1.12c.28 0 .49-.07.7-.21.14-.14.28-.35.35-.63v-.28c0-.21-.07-.35-.21-.42-.14-.07-.35-.14-.7-.14h-.91l-.35 1.68zm8.68 3.71c-.35.77-.7 1.26-.91 1.47-.21.21-.63.7-1.61.7l.07-.63c.84-.28 1.26-1.4 1.54-1.96l-.28-3.78h1.19l.07 2.38.91-2.31h1.05l-2.03 4.13m-2.94-3.85l-.42.28c-.42-.35-.84-.56-1.54-.21-.98.49-1.89 4.13.91 2.94l.14.21h1.12l.7-3.29-.91.07m-.56 1.82c-.21.56-.56.84-.91.77-.28-.14-.35-.63-.21-1.19.21-.56.56-.84.91-.77.28.14.35.63.21 1.19">
								</path>
							</svg>
						</li>
						<li class="icon--payment">
							<svg viewBox="0 0 38 24" xmlns="http://www.w3.org/2000/svg" role="img" width="38" height="24" aria-labelledby="pi-visa">
								<title id="pi-visa">Visa</title>
								<path opacity=".07" d="M35 0H3C1.3 0 0 1.3 0 3v18c0 1.7 1.4 3 3 3h32c1.7 0 3-1.3 3-3V3c0-1.7-1.4-3-3-3z"></path>
								<path fill="#fff" d="M35 1c1.1 0 2 .9 2 2v18c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V3c0-1.1.9-2 2-2h32">
								</path>
								<path d="M28.3 10.1H28c-.4 1-.7 1.5-1 3h1.9c-.3-1.5-.3-2.2-.6-3zm2.9 5.9h-1.7c-.1 0-.1 0-.2-.1l-.2-.9-.1-.2h-2.4c-.1 0-.2 0-.2.2l-.3.9c0 .1-.1.1-.1.1h-2.1l.2-.5L27 8.7c0-.5.3-.7.8-.7h1.5c.1 0 .2 0 .2.2l1.4 6.5c.1.4.2.7.2 1.1.1.1.1.1.1.2zm-13.4-.3l.4-1.8c.1 0 .2.1.2.1.7.3 1.4.5 2.1.4.2 0 .5-.1.7-.2.5-.2.5-.7.1-1.1-.2-.2-.5-.3-.8-.5-.4-.2-.8-.4-1.1-.7-1.2-1-.8-2.4-.1-3.1.6-.4.9-.8 1.7-.8 1.2 0 2.5 0 3.1.2h.1c-.1.6-.2 1.1-.4 1.7-.5-.2-1-.4-1.5-.4-.3 0-.6 0-.9.1-.2 0-.3.1-.4.2-.2.2-.2.5 0 .7l.5.4c.4.2.8.4 1.1.6.5.3 1 .8 1.1 1.4.2.9-.1 1.7-.9 2.3-.5.4-.7.6-1.4.6-1.4 0-2.5.1-3.4-.2-.1.2-.1.2-.2.1zm-3.5.3c.1-.7.1-.7.2-1 .5-2.2 1-4.5 1.4-6.7.1-.2.1-.3.3-.3H18c-.2 1.2-.4 2.1-.7 3.2-.3 1.5-.6 3-1 4.5 0 .2-.1.2-.3.2M5 8.2c0-.1.2-.2.3-.2h3.4c.5 0 .9.3 1 .8l.9 4.4c0 .1 0 .1.1.2 0-.1.1-.1.1-.1l2.1-5.1c-.1-.1 0-.2.1-.2h2.1c0 .1 0 .1-.1.2l-3.1 7.3c-.1.2-.1.3-.2.4-.1.1-.3 0-.5 0H9.7c-.1 0-.2 0-.2-.2L7.9 9.5c-.2-.2-.5-.5-.9-.6-.6-.3-1.7-.5-1.9-.5L5 8.2z" fill="#142688"></path>
							</svg>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>	</footer>
</div>
<script id="byl-www-lower-native-slider">

(function(){
'use strict';
var root=document.querySelector('.byl-www-content .home-module6');
if(!root)return;
var track=root.querySelector('.myswiper-wrapper03');if(!track)return;
var slides=Array.from(track.querySelectorAll('.swiper-slide'));
var next=root.querySelector('.swiper-button-next'),prev=root.querySelector('.swiper-button-prev'),pager=root.querySelector('.swiper-pagination');
function step(){return slides[0]?slides[0].getBoundingClientRect().width+10:track.clientWidth}
function maxPage(){return Math.max(1,Math.ceil((track.scrollWidth-track.clientWidth)/step())+1)}
function update(){
 var index=Math.min(maxPage()-1,Math.round(track.scrollLeft/step()));
 if(pager){
  pager.replaceChildren();
  for(var i=0;i<maxPage();i++){var dot=document.createElement('button');dot.type='button';dot.className='swiper-pagination-bullet'+(i===index?' swiper-pagination-bullet-active':'');dot.setAttribute('aria-label','Go to slide '+(i+1));dot.addEventListener('click',((j)=>()=>track.scrollTo({left:step()*j,behavior:'smooth'}))(i));pager.appendChild(dot)}
 }
 if(prev){prev.disabled=index===0;prev.setAttribute('aria-label','Previous slide');prev.tabIndex=0}
 if(next){next.disabled=index>=maxPage()-1;next.setAttribute('aria-label','Next slide');next.tabIndex=0}
}
function move(n){track.scrollBy({left:step()*n,behavior:'smooth'})}
if(next){next.addEventListener('click',()=>move(1));next.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();move(1)}})}
if(prev){prev.addEventListener('click',()=>move(-1));prev.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();move(-1)}})}
track.addEventListener('scroll',()=>requestAnimationFrame(update),{passive:true});
window.addEventListener('resize',update,{passive:true});update();
})();

</script>
<?php wp_footer(); ?>
</body>
</html>
