<?php
include_once "lib/config_db.php";
include_once "lib/obj_db.php";
include_once "lib/utility.php";
$swis             = new connect_db("swis");
$swis_thaibrother = new connect_db("thaibrother_database");

$next_ten_day_begin = date('Y-m-d');
$next_ten_day_end   = date('Y-m-d', strtotime($next_ten_day_begin . ' + 10 days'));
$tomorrow           = date('Y-m-d', strtotime($next_ten_day_begin . ' + 1 days'));
$time_year          = time_year('');

###########
function count_total_students($time_year)
{
    $tb_db   = new connect_db("thaibrother_database");
    $sql     = "select databases_name from swis_ip where name_shot <> 'FSG' order by pic_level";
    $schools = $tb_db->cache_query($sql);

    $total = 0;
    foreach ($schools as $school)
    {
        $db_name   = $school['databases_name'];
        $school_db = new connect_db($db_name);
        $sql2      = "select count(*) as total from name_class_room ncr
                      join student_class_room scr on ncr.id_class_room = scr.id_class_room
                      where ncr.year = ? and scr.status = 0";
        $result = $school_db->pcache($sql2, array($time_year));
        if (count($result) > 0)
        {
            $total += $result[0]['total'];
        }
    }

    return $total;
}

$total_students = count_total_students($time_year);

###########
function select_pirnt_add_side($time_year)
{
    global $swis;
    $sql   = "select id_side,name_side from edu_side where active = '1' and connect_side = 0 and year_edu= ? order by asc_side ASC ";
    $query = $swis->pcache($sql, array($time_year));
    $html  = "";
    foreach ($query as $row)
    {
        $id_side   = $row['id_side'];
        $name_side = $row['name_side'];
        $html .= "<li><a href='/html_edu/cgi-bin/main_php/print_side.php?id_side=$id_side&time_year=$time_year'>$name_side</a></li>";
    }

    return $html;
}
###########
function select_rome_calendar()
{
    global $swis_thaibrother;
    $today = date('Y-m-d');
    $sql   = "SELECT date, day, liturgical_season_sunday, saints_feasts, special_days_events, historical_events, deceased_brothers FROM rome_calendar WHERE date = ?";
    $query = $swis_thaibrother->pcache($sql, array($today));
    if (count($query) > 0)
    {
        return $query[0];
    }

    return null;
}
###########
function select_banner_photos()
{
    $swis_tb = new connect_db("thaibrother_database");
    $sql     = "select name_edu,databases_name,web_name from swis_ip where pic_status_cen ='1' order by pic_level ASC ";
    $shows   = $swis_tb->pcache($sql, array());
    $photos  = array();

    foreach ($shows as $row)
    {
        $databases_name = $row["databases_name"];
        $web_name       = $row["web_name"];
        $name_edu       = $row["name_edu"];

        if ($databases_name == 'acc')
        {
            continue;
        }

        $sql2 = "select id_pic,pic_title,show_pic,last_update from $databases_name.pic_post where show_picture='1' order by id_pic DESC LIMIT 0,1 ";
        $s    = $swis_tb->pcache($sql2, array());

        if (count($s) > 0)
        {
            foreach ($s as $v)
            {
                $str      = util_base64_url_encode("type=pic_post_small&file=" . $v["show_pic"]);
                $photos[] = array(
                    'school'   => strtoupper($databases_name),
                    'name_edu' => $name_edu,
                    'title'    => strip_tags($v["pic_title"]),
                    'img_url'  => $web_name . "lib/show_img.php?id=" . $str,
                    'link'     => $web_name . 'html_edu/cgi-bin/report/print_picture.php?id_pic=' . $v["id_pic"],
                    'date'     => $v["last_update"],
                );
            }
        }
    }

    return $photos;
}
###########
function select_gallery_schools()
{
    $swis_tb = new connect_db("thaibrother_database");
    $sql     = "select name_edu,databases_name,web_name from swis_ip where pic_status_cen ='1' order by pic_level ASC ";
    $shows   = $swis_tb->pcache($sql, array());
    $photos  = array();

    foreach ($shows as $row)
    {
        $databases_name = $row["databases_name"];
        $web_name       = $row["web_name"];

        $db   = new connect_db($databases_name);
        $sql2 = "select id_pic,pic_title,show_pic,last_update from pic_post where show_picture='1' order by id_pic DESC LIMIT 0,1 ";
        $s    = $db->pcache($sql2, array());

        if (count($s) > 0)
        {
            foreach ($s as $v)
            {
                $str      = util_base64_url_encode("type=pic_post_small&file=" . $v["show_pic"]);
                $photos[] = array(
                    'school'   => strtoupper($databases_name),
                    'title'    => strip_tags($v["pic_title"]),
                    'img_url'  => $web_name . "lib/show_img.php?id=" . $str,
                    'link'     => $web_name . 'html_edu/cgi-bin/report/print_picture.php?id_pic=' . $v["id_pic"],
                    'web_name' => $web_name,
                );
            }
        }
    }

    return $photos;
}
###########
function select_gallery_bsg()
{
    global $swis;
    $sql    = "select id_pic,pic_title,show_pic,last_update from pic_post where show_picture='1' order by id_pic DESC LIMIT 0,8 ";
    $s      = $swis->pcache($sql, array());
    $photos = array();

    if (count($s) > 0)
    {
        foreach ($s as $v)
        {
            $str      = util_base64_url_encode("type=pic_post_small&file=" . $v["show_pic"]);
            $photos[] = array(
                'school'  => 'BSG',
                'title'   => strip_tags($v["pic_title"]),
                'img_url' => "lib/show_img.php?id=" . $str,
                'link'    => '/html_edu/cgi-bin/report/print_picture.php?id_pic=' . $v["id_pic"],
            );
        }
    }

    return $photos;
}
###########

###########
function select_schools_list()
{
    global $swis_thaibrother;
    $sql     = "SELECT name_shot, name_edu, web_url, databases_name FROM swis_ip WHERE name_shot NOT IN('acc','fsg') ORDER BY id_ip ASC";
    $query   = $swis_thaibrother->pcache($sql, array());
    $schools = array();
    // Location map
    $loc = array(
        'ac'   => 'กรุงเทพฯ (บางรัก)', 'acp' => 'กรุงเทพฯ (เซนต์หลุยส์)', 'sg' => 'กรุงเทพฯ (สามเสน)',
        'mc'   => 'เชียงใหม่', 'mcp'         => 'เชียงใหม่', 'mcs'             => 'เชียงใหม่', 'acs' => 'ชลบุรี',
        'slc'  => 'ฉะเชิงเทรา', 'acl'        => 'ลำปาง', 'act'                 => 'กรุงเทพฯ (บางแค)',
        'acr'  => 'ระยอง', 'acu'             => 'อุบลราชธานี', 'acn'           => 'นครราชสีมา',
        'acsp' => 'สมุทรปราการ', 'atn'       => 'นครพนม', 'acep'               => 'สมุทรสาคร (พระราม 2)',
    );
    foreach ($query as $row)
    {
        $code      = strtolower($row['databases_name']);
        $schools[] = array(
            'abbr' => strtoupper($row['databases_name']),
            'name' => $row['name_edu'],
            'url'  => $row['web_url'],
            'loc'  => isset($loc[$code]) ? $loc[$code] : '',
        );
    }

    return $schools;
}
###########

$print_side      = select_pirnt_add_side($time_year);
$rome_calendar   = select_rome_calendar();
$banner_photos   = select_banner_photos();
$gallery_schools = select_gallery_schools();
$gallery_bsg     = select_gallery_bsg();
$schools_list    = select_schools_list();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thai Brothers — เครือข่ายโรงเรียนในเครือมูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย</title>
    <meta name="description"
        content="ศูนย์กลางข่าวสาร กิจกรรม ปฏิทิน และภาพจาก 14 โรงเรียนในเครือมูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย ขับเคลื่อนด้วยระบบ SWIS">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Sarabun:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
    /* ============================================
       DESIGN SYSTEM
       ============================================ */
    :root {
        --navy: #1a3a5c;
        --navy-dark: #0d2640;
        --navy-light: #2c5f8a;
        --burgundy: #8b0000;
        --gold: #c9a74e;
        --gold-dark: #8b6914;
        --cream: #fafaf7;
        --white: #ffffff;
        --gray-100: #f1f3f5;
        --gray-200: #e9ecef;
        --gray-400: #ced4da;
        --gray-600: #6c757d;
        --text: #2a2a2a;
        --text-light: #5a5a5a;
        --text-muted: #8a8a8a;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Sarabun', 'Inter', sans-serif;
        color: var(--text);
        line-height: 1.7;
        background: var(--cream);
        -webkit-font-smoothing: antialiased;
    }

    h1,
    h2,
    h3,
    h4 {
        font-family: 'Playfair Display', 'Sarabun', serif;
        font-weight: 600;
        color: var(--navy);
        line-height: 1.3;
    }

    a {
        transition: all 0.3s ease;
    }

    img {
        max-width: 100%;
        height: auto;
    }

    .container {
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .eyebrow {
        display: inline-block;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 2.5px;
        text-transform: uppercase;
        color: var(--gold-dark);
        margin-bottom: 12px;
    }

    /* ============================================
       NAVIGATION
       ============================================ */
    .site-nav {
        background: var(--white);
        border-bottom: 1px solid var(--gray-200);
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 72px;
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: var(--navy);
    }

    .nav-brand img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .nav-brand .brand-text {
        font-family: 'Playfair Display', serif;
        font-size: 18px;
        font-weight: 600;
    }

    .nav-brand .brand-sub {
        font-size: 11px;
        color: var(--text-muted);
        font-family: 'Sarabun', sans-serif;
        font-weight: 400;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 28px;
        list-style: none;
    }

    .nav-links a {
        text-decoration: none;
        color: var(--text-light);
        font-size: 14px;
        font-weight: 500;
    }

    .nav-links a:hover {
        color: var(--navy);
    }

    .nav-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: var(--navy);
        cursor: pointer;
    }

    /* Nav Dropdown */
    .nav-dropdown {
        position: relative;
    }

    .nav-dropdown>a {
        cursor: pointer;
    }

    .nav-dropdown>a::after {
        content: ' \f107';
        font-family: FontAwesome;
        font-size: 12px;
        margin-left: 4px;
    }

    .nav-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        min-width: 220px;
        padding: 8px 0;
        z-index: 200;
        list-style: none;
    }

    .nav-dropdown:hover .nav-dropdown-menu {
        display: block;
    }

    .nav-dropdown-menu li a {
        display: block;
        padding: 10px 20px;
        font-size: 14px;
        color: var(--text);
        text-decoration: none;
        border-bottom: 1px solid var(--gray-100);
        transition: all 0.2s ease;
    }

    .nav-dropdown-menu li:last-child a {
        border-bottom: none;
    }

    .nav-dropdown-menu li a:hover {
        background: var(--navy);
        color: white;
        padding-left: 24px;
    }

    /* ============================================
       ROME CALENDAR
       ============================================ */
    .rome-cal {
        background: var(--navy-dark);
        padding: 0;
    }

    .rome-cal-inner {
        display: flex;
        align-items: stretch;
        min-height: 56px;
    }

    .rome-cal-date {
        background: linear-gradient(135deg, var(--burgundy) 0%, #6d0000 100%);
        color: white;
        padding: 14px 28px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
        font-family: 'Playfair Display', serif;
        font-size: 16px;
        font-weight: 600;
    }

    .rome-cal-date .cross {
        font-size: 20px;
        opacity: 0.8;
    }

    .rome-cal-items {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 24px;
        flex-wrap: wrap;
        flex: 1;
    }

    .rome-cal-items .rc-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.85);
        padding: 5px 14px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
    }

    .rome-cal-items .rc-tag .rc-label {
        color: var(--gold);
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 767px) {
        .rome-cal-inner {
            flex-direction: column;
        }

        .rome-cal-date {
            padding: 12px 20px;
            font-size: 14px;
            justify-content: center;
        }

        .rome-cal-items {
            padding: 10px 16px;
            justify-content: center;
        }

        .rome-cal-items .rc-tag {
            font-size: 11px;
            padding: 4px 10px;
        }
    }

    /* ============================================
       RANDOM PHOTO BANNER
       ============================================ */
    .photo-banner {
        position: relative;
        height: 420px;
        overflow: hidden;
        background: var(--navy-dark);
    }

    .photo-banner .pb-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        transition: opacity 1s ease;
    }

    .photo-banner .pb-img.active {
        opacity: 1;
    }

    .photo-banner::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom,
                rgba(13, 38, 64, 0.15) 0%,
                rgba(13, 38, 64, 0.05) 40%,
                rgba(13, 38, 64, 0.5) 80%,
                rgba(13, 38, 64, 0.85) 100%);
        z-index: 1;
    }

    .pb-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 2;
        padding: 32px 0;
    }

    .pb-content .container {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
    }

    .pb-info {
        color: white;
    }

    .pb-info .pb-school {
        display: inline-block;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(8px);
        padding: 5px 14px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .pb-info .pb-title {
        font-family: 'Sarabun', sans-serif;
        font-size: 22px;
        font-weight: 600;
        color: white;
        margin-bottom: 4px;
    }

    .pb-info .pb-date {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
    }

    .pb-dots {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .pb-dots .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        border: none;
        cursor: pointer;
        padding: 0;
        transition: all 0.3s ease;
    }

    .pb-dots .dot.active {
        background: var(--gold);
        width: 28px;
        border-radius: 5px;
    }

    @media (max-width: 991px) {
        .photo-banner {
            height: 340px;
        }

        .pb-info .pb-title {
            font-size: 18px;
        }
    }

    @media (max-width: 767px) {
        .photo-banner {
            height: 280px;
        }

        .pb-content {
            padding: 24px 0;
        }

        .pb-content .container {
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }

        .pb-info .pb-title {
            font-size: 16px;
        }

        .pb-info .pb-school {
            font-size: 10px;
        }
    }

    /* ============================================
       HERO
       ============================================ */
    .hero {
        background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 50%, var(--navy-light) 100%);
        color: white;
        padding: 80px 0 70px;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: -40%;
        right: -15%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(201, 167, 78, 0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .hero .container {
        position: relative;
        z-index: 1;
    }

    .hero-content {
        max-width: 640px;
    }

    .hero .eyebrow {
        color: var(--gold);
        margin-bottom: 16px;
    }

    .hero h1 {
        font-size: 38px;
        color: white;
        margin-bottom: 16px;
        line-height: 1.25;
    }

    .hero .hero-desc {
        font-size: 17px;
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.8;
        margin-bottom: 28px;
    }

    .hero-stats {
        display: flex;
        gap: 40px;
        margin-top: 10px;
    }

    .hero-stat .stat-num {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        font-weight: 700;
        color: var(--gold);
    }

    .hero-stat .stat-lbl {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 2px;
    }

    /* ============================================
       SECTION COMMON
       ============================================ */
    .section {
        padding: 70px 0;
    }

    .section.alt {
        background: var(--white);
    }

    .section-header {
        margin-bottom: 36px;
    }

    .section-header h2 {
        font-size: 28px;
        margin-bottom: 8px;
    }

    .section-header p {
        font-size: 15px;
        color: var(--text-light);
    }

    .section-header .view-all-link {
        float: right;
        font-size: 14px;
        color: var(--navy-light);
        text-decoration: none;
        font-weight: 500;
        margin-top: 8px;
    }

    .section-header .view-all-link:hover {
        color: var(--burgundy);
    }

    /* ============================================
       NEWS GRID
       ============================================ */
    .news-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .news-card {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .section.alt .news-card {
        background: var(--cream);
    }

    .news-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(26, 58, 92, 0.1);
    }

    .news-card img {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }

    .news-card .card-body {
        padding: 20px;
    }

    .news-card .card-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .news-card .school-tag {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 4px;
        background: var(--navy);
        color: white;
        font-family: 'Inter', sans-serif;
    }

    .news-card .card-date {
        font-size: 12px;
        color: var(--text-muted);
    }

    .news-card h4 {
        font-size: 15px;
        font-family: 'Sarabun', sans-serif;
        font-weight: 600;
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .news-card p {
        font-size: 13px;
        color: var(--text-light);
        line-height: 1.6;
    }

    /* ============================================
       SWIS HIGHLIGHT BAR
       ============================================ */
    .swis-bar {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%);
        padding: 50px 0;
        color: white;
    }

    .swis-bar-inner {
        display: flex;
        align-items: center;
        gap: 50px;
    }

    .swis-bar-text {
        flex: 1;
    }

    .swis-bar-text h2 {
        color: white;
        font-size: 24px;
        margin-bottom: 12px;
    }

    .swis-bar-text p {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.75);
        line-height: 1.8;
    }

    .swis-bar-text p strong {
        color: var(--gold);
    }

    .swis-bar-stats {
        display: flex;
        gap: 32px;
        flex-shrink: 0;
    }

    .swis-bar-stats .s-item {
        text-align: center;
    }

    .swis-bar-stats .s-num {
        font-family: 'Playfair Display', serif;
        font-size: 34px;
        font-weight: 700;
        color: var(--gold);
    }

    .swis-bar-stats .s-lbl {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 4px;
    }

    /* ============================================
       CALENDAR
       ============================================ */
    .cal-section-content {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        padding: 28px;
        min-height: 200px;
    }

    .cal-btn-group {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .cal-btn-group .btn-cal {
        display: inline-block;
        padding: 10px 22px;
        background: var(--burgundy);
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .cal-btn-group .btn-cal:hover {
        background: var(--navy-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        color: white;
        text-decoration: none;
    }

    .cal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .cal-header h3 {
        font-size: 20px;
        font-family: 'Sarabun', sans-serif;
        font-weight: 600;
    }

    .cal-nav-btn {
        background: none;
        border: 1px solid var(--gray-400);
        border-radius: 6px;
        padding: 6px 12px;
        cursor: pointer;
        color: var(--text);
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .cal-nav-btn:hover {
        background: var(--navy);
        color: white;
        border-color: var(--navy);
    }

    .cal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: 4px;
    }

    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .cal-day {
        min-height: 90px;
        padding: 6px 8px;
        border-bottom: 1px solid var(--gray-100);
        border-right: 1px solid var(--gray-100);
        font-size: 13px;
    }

    .cal-day:nth-child(7n) {
        border-right: none;
    }

    .cal-day .day-num {
        font-weight: 600;
        color: var(--text);
        display: block;
        margin-bottom: 4px;
    }

    .cal-day.other {
        opacity: 0.3;
    }

    .cal-day.today {
        background: rgba(26, 58, 92, 0.05);
    }

    .cal-day.today .day-num {
        color: var(--burgundy);
    }

    .cal-event {
        display: block;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 3px;
        margin-bottom: 2px;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-decoration: none;
    }

    a.cal-event:hover {
        opacity: 0.85;
        color: white;
        text-decoration: none;
    }

    /* School colors */
    .cal-event.AC {
        background: #1a5276;
    }

    .cal-event.ACP {
        background: #117a65;
    }

    .cal-event.SG {
        background: #6c3483;
    }

    .cal-event.MC {
        background: #b7950b;
    }

    .cal-event.MCP {
        background: #b7950b;
    }

    .cal-event.MCS {
        background: #b7950b;
    }

    .cal-event.ACS {
        background: #cb4335;
    }

    .cal-event.SLC {
        background: #2e86c1;
    }

    .cal-event.ACL {
        background: #d35400;
    }

    .cal-event.ACT {
        background: #1abc9c;
    }

    .cal-event.ACR {
        background: #8e44ad;
    }

    .cal-event.ACU {
        background: #27ae60;
    }

    .cal-event.ACN {
        background: #e67e22;
    }

    .cal-event.ACSP {
        background: #2980b9;
    }

    .cal-event.ATN {
        background: #c0392b;
    }

    .cal-event.ACEP {
        background: #16a085;
    }

    .cal-event.BSG {
        background: #8b6914;
    }

    .cal-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--gray-200);
    }

    .cal-legend .leg {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: var(--text-light);
    }

    .cal-legend .leg .dot {
        width: 10px;
        height: 10px;
        border-radius: 3px;
    }

    /* ============================================
       GALLERY — magazine layout
       ============================================ */
    .gallery-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto;
        gap: 12px;
    }

    .gallery-feature {
        grid-row: 1 / 3;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: auto;
    }

    .gallery-feature img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }

    .gallery-feature:hover img {
        transform: scale(1.04);
    }

    .gallery-side {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .gallery-item {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        aspect-ratio: 4/3;
        cursor: pointer;
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.06);
    }

    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 10px 14px;
        background: linear-gradient(transparent 0%, rgba(0, 0, 0, 0.65) 100%);
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
    }

    .gallery-overlay .g-school {
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        font-weight: 700;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(4px);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }

    .gallery-overlay .g-title {
        font-size: 12px;
        color: white;
        flex: 1;
        margin-left: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .gallery-feature .gallery-overlay {
        padding: 20px 24px;
    }

    .gallery-feature .gallery-overlay .g-school {
        font-size: 12px;
        padding: 4px 12px;
    }

    .gallery-feature .gallery-overlay .g-title {
        font-size: 15px;
        font-weight: 500;
    }

    .gallery-more {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        text-decoration: none;
        border-radius: 10px;
        background: var(--navy);
        color: rgba(255, 255, 255, 0.8);
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        aspect-ratio: 4/3;
    }

    .gallery-more:hover {
        background: var(--navy-light);
        color: white;
        text-decoration: none;
        transform: scale(1.02);
    }

    .gallery-more .gm-inner {
        text-align: center;
    }

    .gallery-more .gm-inner .fa {
        display: block;
        font-size: 24px;
        margin-bottom: 8px;
        color: var(--gold);
    }

    .gallery-more .gm-inner span {
        font-size: 13px;
    }

    /* ============================================
       FANPAGE — social cards
       ============================================ */
    .fp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 14px;
    }

    .fp-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        text-decoration: none;
        color: var(--text);
        transition: all 0.3s ease;
    }

    .section.alt .fp-card {
        background: var(--cream);
    }

    .fp-card:hover {
        box-shadow: 0 6px 18px rgba(26, 58, 92, 0.1);
        transform: translateY(-2px);
        text-decoration: none;
        color: var(--text);
        border-color: #4267B2;
    }

    .fp-card img {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid var(--gray-200);
    }

    .fp-card .fp-info {
        flex: 1;
        min-width: 0;
    }

    .fp-card .fp-code {
        display: block;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 700;
        color: var(--navy);
        letter-spacing: 0.5px;
    }

    .fp-card .fp-name {
        display: block;
        font-size: 13px;
        color: var(--text-light);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .fp-card .fp-fb-icon {
        font-size: 22px;
        color: #4267B2;
        flex-shrink: 0;
        opacity: 0.6;
        transition: opacity 0.3s ease;
    }

    .fp-card:hover .fp-fb-icon {
        opacity: 1;
    }

    /* ============================================
       SOFTWARE UPDATES — list cards
       ============================================ */
    .upd-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .upd-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 20px;
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        text-decoration: none;
        color: var(--text);
        transition: all 0.3s ease;
    }

    .upd-item:hover {
        box-shadow: 0 4px 14px rgba(26, 58, 92, 0.08);
        transform: translateX(4px);
        text-decoration: none;
        color: var(--text);
        border-left: 3px solid var(--gold);
    }

    .upd-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--navy);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .upd-body {
        flex: 1;
        min-width: 0;
    }

    .upd-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--navy);
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .upd-item:hover .upd-title {
        color: var(--burgundy);
    }

    .upd-meta {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .upd-module {
        display: inline-block;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 4px;
        background: var(--gold);
        color: var(--navy);
        font-weight: 600;
    }

    .upd-new {
        display: inline-block;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        background: #e74c3c;
        color: white;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .upd-date {
        font-size: 12px;
        color: var(--text-muted);
        flex-shrink: 0;
        white-space: nowrap;
    }

    .upd-viewall {
        display: inline-block;
        font-size: 14px;
        color: var(--navy);
        font-weight: 600;
        text-decoration: none;
        padding: 8px 20px;
        border: 1px solid var(--navy);
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .upd-viewall:hover {
        background: var(--navy);
        color: white;
        text-decoration: none;
    }

    /* ============================================
       SCHOOLS GRID
       ============================================ */
    .schools-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
    }

    .school-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        text-decoration: none;
        color: var(--text);
        border-left: 4px solid var(--gold);
        transition: all 0.3s ease;
    }

    .section.alt .school-card {
        background: var(--cream);
    }

    .school-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(26, 58, 92, 0.1);
        border-left-color: var(--burgundy);
        text-decoration: none;
        color: var(--text);
    }

    .school-card .s-abbr {
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 700;
        color: white;
        background: var(--navy);
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .school-card .s-info h4 {
        font-size: 14px;
        font-family: 'Sarabun', sans-serif;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .school-card:hover .s-info h4 {
        color: var(--burgundy);
    }

    .school-card .s-info .s-loc {
        font-size: 12px;
        color: var(--text-muted);
    }

    /* ============================================
       FEATURES / SERVICES
       ============================================ */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .feat-card {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        padding: 28px 24px;
        text-align: center;
        text-decoration: none;
        color: var(--text);
        transition: all 0.3s ease;
    }

    .feat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(26, 58, 92, 0.1);
        text-decoration: none;
        color: var(--text);
    }

    .feat-card .feat-icon {
        font-size: 32px;
        color: var(--navy);
        margin-bottom: 14px;
    }

    .feat-card h4 {
        font-size: 16px;
        font-family: 'Sarabun', sans-serif;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .feat-card:hover h4 {
        color: var(--burgundy);
    }

    .feat-card p {
        font-size: 13px;
        color: var(--text-light);
        line-height: 1.6;
    }

    /* ============================================
       NETWORK
       ============================================ */
    .network-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .network-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px 24px;
        background: var(--cream);
        border: 1px solid var(--gray-200);
        border-radius: 10px;
        text-decoration: none;
        color: var(--text);
        border-left: 4px solid var(--gold);
        transition: all 0.3s ease;
    }

    .network-card:hover {
        background: white;
        border-left-color: var(--burgundy);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(26, 58, 92, 0.1);
        text-decoration: none;
        color: var(--text);
    }

    .network-card .nw-icon {
        font-size: 24px;
        color: var(--navy);
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .network-card:hover .nw-icon {
        color: var(--burgundy);
    }

    .network-card .nw-info h4 {
        font-size: 15px;
        font-family: 'Sarabun', sans-serif;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .network-card:hover .nw-info h4 {
        color: var(--burgundy);
    }

    .network-card .nw-info .nw-desc {
        font-size: 13px;
        color: var(--text-muted);
    }

    /* ============================================
       FOOTER
       ============================================ */
    footer {
        background: var(--navy-dark);
        color: rgba(255, 255, 255, 0.75);
        padding: 60px 0 30px;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }

    footer h4 {
        color: white;
        font-size: 18px;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--gold);
        display: inline-block;
        font-family: 'Playfair Display', serif;
    }

    footer p {
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 10px;
    }

    footer .fa {
        color: var(--gold);
        width: 18px;
        margin-right: 8px;
    }

    footer a {
        color: var(--gold);
        text-decoration: none;
    }

    footer a:hover {
        color: white;
    }

    .copyright {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 24px;
        text-align: center;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.5);
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 991px) {
        .hero h1 {
            font-size: 30px;
        }

        .hero-stats {
            gap: 28px;
        }

        .news-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .gallery-side {
            grid-template-columns: 1fr 1fr;
        }

        .swis-bar-inner {
            flex-direction: column;
            text-align: center;
        }

        .swis-bar-stats {
            justify-content: center;
        }

        .cal-day {
            min-height: 70px;
            padding: 4px 6px;
        }

        .cal-event {
            font-size: 10px;
            padding: 1px 4px;
        }

        .cal-header h3 {
            font-size: 18px;
        }

        .fp-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }
    }

    @media (max-width: 767px) {
        .nav-links {
            display: none;
        }

        .nav-toggle {
            display: block;
        }

        .nav-links.open {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 72px;
            left: 0;
            right: 0;
            background: white;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            z-index: 99;
        }

        .nav-links.open .nav-dropdown-menu {
            position: static;
            box-shadow: none;
            border: none;
            padding-left: 16px;
        }

        .hero {
            padding: 60px 0 50px;
        }

        .hero h1 {
            font-size: 26px;
        }

        .hero-stats {
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero-stat .stat-num {
            font-size: 26px;
        }

        .section {
            padding: 50px 0;
        }

        .section-header h2 {
            font-size: 24px;
        }

        .news-grid {
            grid-template-columns: 1fr;
        }

        .gallery-layout {
            grid-template-columns: 1fr;
        }

        .gallery-feature {
            grid-row: auto;
        }

        .gallery-side {
            grid-template-columns: 1fr 1fr;
        }

        .schools-grid {
            grid-template-columns: 1fr;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }

        .network-grid {
            grid-template-columns: 1fr;
        }

        .footer-grid {
            grid-template-columns: 1fr;
        }

        .cal-section-content {
            padding: 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .cal-day {
            min-height: 50px;
            padding: 2px;
        }

        .cal-day .day-num {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .cal-event {
            font-size: 0;
            padding: 0;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin: 1px;
        }

        .cal-weekdays {
            font-size: 11px;
        }

        .cal-header h3 {
            font-size: 16px;
        }

        .cal-legend {
            gap: 6px;
            flex-wrap: wrap;
        }

        .cal-legend .leg {
            font-size: 9px;
        }

        .cal-btn-group {
            justify-content: center;
        }

        .cal-btn-group .btn-cal {
            padding: 8px 14px;
            font-size: 12px;
        }

        .fp-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .fp-card {
            padding: 10px 12px;
            gap: 10px;
        }

        .fp-card img {
            width: 36px;
            height: 36px;
        }

        .upd-item {
            padding: 12px 14px;
            gap: 12px;
        }

        .upd-title {
            font-size: 13px;
        }
    }
    </style>
</head>

<body>

    <!-- ============================================
         NAVIGATION
         ============================================ -->
    <nav class="site-nav">
        <div class="container">
            <div class="nav-inner">
                <a href="#" class="nav-brand">
                    <img src="https://www.thaibrothers.net/images/logo.png" alt="Logo">
                    <div>
                        <div class="brand-text">Thai Brothers</div>
                        <div class="brand-sub">เครือข่ายโรงเรียนในเครือมูลนิธิฯ</div>
                    </div>
                </a>
                <button class="nav-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')">
                    <i class="fa fa-bars"></i>
                </button>
                <ul class="nav-links">
                    <li><a href="https://thaibrothers.net/admin/" target="_blank">SWIS Plus</a></li>
                    <li class="nav-dropdown">
                        <a href="#">ฝ่ายบริหาร</a>
                        <ul class="nav-dropdown-menu">
                            <?php echo $print_side; ?>
                        </ul>
                    </li>
                    <li><a href="#news">ข่าวสาร</a></li>
                    <li><a href="#calendar">ปฏิทิน</a></li>
                    <li><a href="#gallery">อัลบั้มภาพ</a></li>
                    <li><a href="#schools">โรงเรียน</a></li>
                    <li><a href="https://thaibrothers.net/school-history.html" target="_blank">ประวัติโรงเรียน</a></li>
                    <li><a href="https://thaibrothers.net/strategic-plan.html" target="_blank">แผนยุทธศาสตร์</a></li>
                    <li><a href="https://thaibrothers.net/spirit.html" target="_blank">จิตตารมณ์สู่ภารกิจ</a></li>
                    <li><a href="#contact">ติดต่อ</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ============================================
         ROME CALENDAR
         ============================================ -->
    <?php if ($rome_calendar): ?>
    <section class="rome-cal">
        <div class="container">
            <div class="rome-cal-inner">
                <div class="rome-cal-date">
                    <span class="cross">&#10014;</span>
                    <?php echo date('M j', strtotime($rome_calendar['date'])); ?> &middot;
                    <?php echo htmlspecialchars($rome_calendar['day']); ?>
                </div>
                <div class="rome-cal-items">
                    <?php if (!empty($rome_calendar['liturgical_season_sunday'])): ?>
                    <span class="rc-tag"><span class="rc-label">Season</span>
                        <?php echo htmlspecialchars($rome_calendar['liturgical_season_sunday']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($rome_calendar['saints_feasts'])): ?>
                    <span class="rc-tag"><span class="rc-label">Saints</span>
                        <?php echo htmlspecialchars($rome_calendar['saints_feasts']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($rome_calendar['special_days_events'])): ?>
                    <span class="rc-tag"><span class="rc-label">Special</span>
                        <?php echo htmlspecialchars($rome_calendar['special_days_events']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($rome_calendar['historical_events'])): ?>
                    <span class="rc-tag"><span class="rc-label">History</span>
                        <?php echo htmlspecialchars($rome_calendar['historical_events']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($rome_calendar['deceased_brothers'])): ?>
                    <span class="rc-tag"><span class="rc-label">Deceased</span>
                        <?php echo htmlspecialchars($rome_calendar['deceased_brothers']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================
         RANDOM PHOTO BANNER
         ============================================ -->
    <?php if (count($banner_photos) > 0): ?>
    <section class="photo-banner" id="photoBanner">
        <?php foreach ($banner_photos as $i => $photo): ?>
        <img class="pb-img <?php echo($i === 0) ? 'active' : ''; ?>"
            src="<?php echo htmlspecialchars($photo['img_url']); ?>"
            alt="<?php echo htmlspecialchars($photo['title']); ?>"
            data-school="<?php echo htmlspecialchars($photo['school']); ?>"
            data-title="<?php echo htmlspecialchars($photo['title']); ?>"
            data-date="<?php echo htmlspecialchars($photo['date']); ?>">
        <?php endforeach; ?>

        <div class="pb-content">
            <div class="container">
                <div class="pb-info">
                    <span class="pb-school"
                        id="pbSchool"><?php echo htmlspecialchars($banner_photos[0]['school']); ?></span>
                    <div class="pb-title" id="pbTitle"><?php echo htmlspecialchars($banner_photos[0]['title']); ?></div>
                    <div class="pb-date" id="pbDate"><?php echo htmlspecialchars($banner_photos[0]['date']); ?></div>
                </div>
                <div class="pb-dots" id="pbDots"></div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================
         HERO
         ============================================ -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <span class="eyebrow">Thai Brothers Network</span>
                <h1>เครือข่ายโรงเรียนในเครือ<br>มูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย</h1>
                <p class="hero-desc">
                    ศูนย์กลางข่าวสาร ภาพกิจกรรม และปฏิทินจาก 14 โรงเรียนในเครือฯ ทั่วประเทศ
                    ขับเคลื่อนด้วยระบบ SWIS (School Web-based Information System)
                    และแผนงาน PDCA 9 ขั้นตอนที่ออกแบบโดยมูลนิธิฯ
                </p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="stat-num">14</div>
                        <div class="stat-lbl">โรงเรียนในเครือ</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-num">125+</div>
                        <div class="stat-lbl">ปีแห่งพันธกิจ</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-num">220</div>
                        <div class="stat-lbl">ฟังก์ชัน SWIS</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-num"><?php echo floor($total_students / 1000) . 'K'; ?></div>
                        <div class="stat-lbl">นักเรียนปัจจุบัน</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         GALLERY — SCHOOLS
         ============================================ -->
    <section id="gallery" class="section alt">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">อัลบั้มภาพ</span>
                <h2>ภาพกิจกรรมจากโรงเรียนในเครือฯ</h2>
                <a href="https://www.thaibrothers.net/print_title_pic_all.php" target="_blank"
                    class="view-all-link">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
                <p>ภาพบรรยากาศกิจกรรมล่าสุดจากโรงเรียนในเครือมูลนิธิฯ</p>
            </div>
            <?php if (count($gallery_schools) > 0): ?>
            <div class="gallery-layout">
                <!-- Featured image (left, full height) -->
                <a href="<?php echo htmlspecialchars($gallery_schools[0]['link']); ?>" target="_blank"
                    class="gallery-feature gallery-item">
                    <img src="<?php echo htmlspecialchars($gallery_schools[0]['img_url']); ?>"
                        alt="<?php echo htmlspecialchars($gallery_schools[0]['title']); ?>">
                    <div class="gallery-overlay">
                        <span class="g-school"><?php echo htmlspecialchars($gallery_schools[0]['school']); ?></span>
                        <span class="g-title"><?php echo htmlspecialchars($gallery_schools[0]['title']); ?></span>
                    </div>
                </a>

                <!-- Top row (right) -->
                <div class="gallery-side">
                    <?php
$top_items = array_slice($gallery_schools, 1, 4);
foreach ($top_items as $photo):
?>
                    <a href="<?php echo htmlspecialchars($photo['link']); ?>" target="_blank" class="gallery-item">
                        <img src="<?php echo htmlspecialchars($photo['img_url']); ?>"
                            alt="<?php echo htmlspecialchars($photo['title']); ?>">
                        <div class="gallery-overlay">
                            <span class="g-school"><?php echo htmlspecialchars($photo['school']); ?></span>
                            <span class="g-title"><?php echo htmlspecialchars($photo['title']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Bottom row (right) -->
                <div class="gallery-side">
                    <?php
$bottom_items = array_slice($gallery_schools, 5, 3);
foreach ($bottom_items as $photo):
?>
                    <a href="<?php echo htmlspecialchars($photo['link']); ?>" target="_blank" class="gallery-item">
                        <img src="<?php echo htmlspecialchars($photo['img_url']); ?>"
                            alt="<?php echo htmlspecialchars($photo['title']); ?>">
                        <div class="gallery-overlay">
                            <span class="g-school"><?php echo htmlspecialchars($photo['school']); ?></span>
                            <span class="g-title"><?php echo htmlspecialchars($photo['title']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <a href="https://www.thaibrothers.net/print_title_pic_all.php" target="_blank" class="gallery-more">
                        <div class="gm-inner">
                            <i class="fa fa-th"></i>
                            <span>ดูอัลบั้มทั้งหมด</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================
         GALLERY — BSG (Foundation)
         ============================================ -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">มูลนิธิฯ</span>
                <h2>อัลบั้มภาพมูลนิธิฯ</h2>
                <a href="https://thaibrothers.net/html_edu/cgi-bin/picture_school/print_picture_school_all.php"
                    target="_blank" class="view-all-link">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
                <p>ภาพกิจกรรมจากมูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย</p>
            </div>
            <?php if (count($gallery_bsg) > 0): ?>
            <div class="gallery-layout">
                <!-- Featured image -->
                <a href="<?php echo htmlspecialchars($gallery_bsg[0]['link']); ?>" target="_blank"
                    class="gallery-feature gallery-item">
                    <img src="<?php echo htmlspecialchars($gallery_bsg[0]['img_url']); ?>"
                        alt="<?php echo htmlspecialchars($gallery_bsg[0]['title']); ?>">
                    <div class="gallery-overlay">
                        <span class="g-school"><?php echo htmlspecialchars($gallery_bsg[0]['school']); ?></span>
                        <span class="g-title"><?php echo htmlspecialchars($gallery_bsg[0]['title']); ?></span>
                    </div>
                </a>

                <!-- Top row -->
                <div class="gallery-side">
                    <?php
$bsg_top = array_slice($gallery_bsg, 1, 4);
foreach ($bsg_top as $photo):
?>
                    <a href="<?php echo htmlspecialchars($photo['link']); ?>" target="_blank" class="gallery-item">
                        <img src="<?php echo htmlspecialchars($photo['img_url']); ?>"
                            alt="<?php echo htmlspecialchars($photo['title']); ?>">
                        <div class="gallery-overlay">
                            <span class="g-school"><?php echo htmlspecialchars($photo['school']); ?></span>
                            <span class="g-title"><?php echo htmlspecialchars($photo['title']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Bottom row -->
                <div class="gallery-side">
                    <?php
$bsg_bottom = array_slice($gallery_bsg, 5, 3);
foreach ($bsg_bottom as $photo):
?>
                    <a href="<?php echo htmlspecialchars($photo['link']); ?>" target="_blank" class="gallery-item">
                        <img src="<?php echo htmlspecialchars($photo['img_url']); ?>"
                            alt="<?php echo htmlspecialchars($photo['title']); ?>">
                        <div class="gallery-overlay">
                            <span class="g-school"><?php echo htmlspecialchars($photo['school']); ?></span>
                            <span class="g-title"><?php echo htmlspecialchars($photo['title']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <a href="https://thaibrothers.net/html_edu/cgi-bin/picture_school/print_picture_school_all.php"
                        target="_blank" class="gallery-more">
                        <div class="gm-inner">
                            <i class="fa fa-th"></i>
                            <span>ดูอัลบั้มทั้งหมด</span>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================
         NEWS — SCHOOLS (AJAX)
         ============================================ -->
    <section id="news" class="section">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">ข่าวสารล่าสุด</span>
                <h2>ข่าวจากโรงเรียนในเครือฯ</h2>
                <a href="view_all_news_v5.php" target="_blank" class="view-all-link">ดูทั้งหมด <i
                        class="fa fa-arrow-right"></i></a>
                <p>อัพเดทกิจกรรมและข่าวสารล่าสุดจาก 14 โรงเรียนทั่วประเทศ</p>
            </div>
            <div id="jlayer3">Loading...</div>
        </div>
    </section>

    <!-- ============================================
         NEWS — FOUNDATION (AJAX)
         ============================================ -->
    <section class="section alt">
        <div class="container">
            <div class="section-header">
                <h2>ข่าวจากมูลนิธิฯ</h2>
                <a href="html_edu/cgi-bin/report/print_news_active.php?type=2" target="_blank"
                    class="view-all-link">ดูทั้งหมด <i class="fa fa-arrow-right"></i></a>
                <p>ความเคลื่อนไหวจากมูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย</p>
            </div>
            <div id="jlayer9">Loading...</div>
        </div>
    </section>

    <!-- ============================================
         SWIS HIGHLIGHT
         ============================================ -->
    <section class="swis-bar">
        <div class="container">
            <div class="swis-bar-inner">
                <div class="swis-bar-text">
                    <span class="eyebrow" style="color:var(--gold);">ขับเคลื่อนด้วย SWIS</span>
                    <h2>ระบบสารสนเทศกลางของเครือข่าย</h2>
                    <p>
                        ข้อมูลทั้งหมดบนเว็บไซต์นี้มาจากระบบ <strong>SWIS</strong> ของแต่ละโรงเรียน
                        ซึ่งใช้ <strong>โมดูลแผนงาน PDCA 9 ขั้นตอน</strong> ที่มูลนิธิฯ ออกแบบเอง
                        เป็นเครื่องมือขับเคลื่อนทุกงานและกิจกรรม ตั้งแต่วางแผนจนถึงประเมินผล
                    </p>
                </div>
                <div class="swis-bar-stats">
                    <div class="s-item">
                        <div class="s-num">14</div>
                        <div class="s-lbl">โรงเรียน</div>
                    </div>
                    <div class="s-item">
                        <div class="s-num">220</div>
                        <div class="s-lbl">ฟังก์ชัน</div>
                    </div>
                    <div class="s-item">
                        <div class="s-num">25+</div>
                        <div class="s-lbl">ปีพัฒนา</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         CALENDAR (AJAX)
         ============================================ -->
    <section id="calendar" class="section">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">ปฏิทินกิจกรรม</span>
                <h2>ปฏิทินรวมเครือข่าย</h2>
                <p>กิจกรรมจากทุกโรงเรียนในเครือมูลนิธิฯ</p>
            </div>
            <div class="cal-btn-group">
                <a href="https://thaibrothers.net/today_events.php" class="btn-cal" target="_blank"><i
                        class="fa fa-calendar-check-o"></i> Today's Events</a>
                <a href="today_events.php?date_ymd=<?php echo $tomorrow; ?>" class="btn-cal" target="_blank"><i
                        class="fa fa-calendar-plus-o"></i> Tomorrow</a>
                <a href="today_events.php?next_ten_day_begin=<?php echo $next_ten_day_begin; ?>&next_ten_day_end=<?php echo $next_ten_day_end; ?>"
                    class="btn-cal" target="_blank"><i class="fa fa-calendar"></i> Next 10 Days</a>
            </div>
            <div class="cal-section-content">
                <div id="jlayer2">Loading...</div>
            </div>
        </div>
    </section>

    <!-- ============================================
         FANPAGE
         ============================================ -->
    <section class="section alt">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">Social Media</span>
                <h2>ติดตามจาก Fanpage</h2>
            </div>
            <div id="jlayer_fanpage">Loading...</div>
        </div>
    </section>

    <!-- ============================================
         SOFTWARE UPDATES (AJAX)
         ============================================ -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">Development</span>
                <h2>Update Version Module by SWIS Plus</h2>
            </div>
            <div id="jlayer_updates">Loading...</div>
        </div>
    </section>

    <!-- ============================================
         SCHOOLS
         ============================================ -->
    <section id="schools" class="section alt">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">โรงเรียนในเครือ</span>
                <h2>14 โรงเรียน ทั่วประเทศไทย</h2>
            </div>
            <div class="schools-grid">
                <?php foreach ($schools_list as $sc): ?>
                <a href="<?php echo htmlspecialchars($sc['url']); ?>" target="_blank" class="school-card">
                    <span class="s-abbr"><?php echo htmlspecialchars($sc['abbr']); ?></span>
                    <div class="s-info">
                        <h4><?php echo htmlspecialchars($sc['name']); ?></h4>
                        <div class="s-loc"><?php echo htmlspecialchars($sc['loc']); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================
         FEATURES / SERVICES
         ============================================ -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">บริการเพิ่มเติม</span>
                <h2>แหล่งข้อมูลและบริการ</h2>
            </div>
            <div class="features-grid">
                <a href="https://thaibrothers.net/chiness/" target="_blank" class="feat-card">
                    <div class="feat-icon"><i class="fa fa-book"></i></div>
                    <h4>แบบเรียนภาษาจีน</h4>
                    <p>แหล่งเรียนรู้ภาษาจีนสำหรับนักเรียนในเครือฯ</p>
                </a>
                <a href="http://www.thaibrothers.net/html_edu/cgi-bin/thesis/print_form_download.php" target="_blank"
                    class="feat-card">
                    <div class="feat-icon"><i class="fa fa-graduation-cap"></i></div>
                    <h4>ฐานข้อมูลวิทยานิพนธ์</h4>
                    <p>รวมวิทยานิพนธ์และงานวิจัยของบุคลากรในเครือฯ</p>
                </a>
                <a href="https://thaibrothers.net/alumni/" target="_blank" class="feat-card">
                    <div class="feat-icon"><i class="fa fa-users"></i></div>
                    <h4>ลงทะเบียนศิษย์เก่า</h4>
                    <p>เครือข่ายศิษย์เก่าโรงเรียนในเครือมูลนิธิฯ</p>
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================
         NETWORK
         ============================================ -->
    <section class="section alt">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">เครือข่ายสากล</span>
                <h2>เว็บไซต์ที่เกี่ยวข้อง</h2>
            </div>
            <div class="network-grid">
                <a href="https://www.montfortian.net" target="_blank" class="network-card">
                    <div class="nw-icon"><i class="fa fa-globe"></i></div>
                    <div class="nw-info">
                        <h4>Montfortian Network</h4>
                        <div class="nw-desc">เครือข่ายคณะมงฟอร์ต 3 คณะทั่วโลก</div>
                    </div>
                </a>
                <a href="https://www.stgabrielinst.org" target="_blank" class="network-card">
                    <div class="nw-icon"><i class="fa fa-university"></i></div>
                    <div class="nw-info">
                        <h4>St.Gabriel Foundation (Rome)</h4>
                        <div class="nw-desc">ศูนย์กลางคณะภราดาเซนต์คาเบรียลทั่วโลก</div>
                    </div>
                </a>
                <a href="https://swisplus.com" target="_blank" class="network-card">
                    <div class="nw-icon"><i class="fa fa-code"></i></div>
                    <div class="nw-info">
                        <h4>SWIS Plus</h4>
                        <div class="nw-desc">ผู้พัฒนาระบบ SWIS</div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================
         FOOTER
         ============================================ -->
    <footer id="contact">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>ติดต่อเรา</h4>
                    <p><i class="fa fa-map-marker"></i> อาคารมูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย<br>
                        2 ซอยแสงเงิน (ทองหล่อ 25) ถนนสุขุมวิท 55<br>
                        แขวงคลองตันเหนือ เขตวัฒนา กรุงเทพฯ 10110</p>
                    <p><i class="fa fa-phone"></i> (02) 712-9010 &middot; โทรสาร (02) 390-2292</p>
                    <p><i class="fa fa-envelope"></i> swiscenter@gmail.com</p>
                </div>
                <div>
                    <h4>Thai Brothers Network</h4>
                    <p>
                        เว็บไซต์นี้เป็นศูนย์กลางเชื่อมโยงข้อมูลจากระบบ SWIS
                        ของโรงเรียนในเครือมูลนิธิคณะเซนต์คาเบรียลแห่งประเทศไทย
                        ดูแลโดย ฝ่ายเทคโนโลยีสารสนเทศเพื่อการบริหารและการศึกษา
                    </p>
                    <div id="fb-root"></div>
                    <script>
                    (function(d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) return;
                        js = d.createElement(s);
                        js.id = id;
                        js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8&appId=123915407768036";
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));
                    </script>
                    <div class="fb-page" data-href="https://www.facebook.com/BSG2444/" data-width="340"
                        data-small-header="true" data-adapt-container-width="true" data-hide-cover="false"
                        data-show-facepile="true">
                    </div>
                </div>
            </div>
            <div class="copyright">
                Copyright &copy; thaibrothers.net 2004&ndash;<?php echo date('Y'); ?> &middot; swiscenter@gmail.com
                &nbsp;&middot;&nbsp;
                Part of <a href="https://www.montfortian.net" target="_blank">Montfortian Network</a>
                &nbsp;
                <img src="images/swis.png" width="40" height="40" alt="SWIS"
                    style="vertical-align:middle; opacity:0.5;">
            </div>
        </div>
    </footer>

    <!-- jQuery (for AJAX loads) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

    <script>
    // AJAX load content
    $('#jlayer2').load('ajax_calendar_v5.php');
    $('#jlayer3').load('ajax_news_school_v5.php');
    $('#jlayer_fanpage').load('ajax_fanpage_v5.php');
    $('#jlayer9').load('ajax_news_bsg_v5.php');
    $('#jlayer_updates').load('ajax_updates_v5.php?per=10');

    // Calendar month navigation
    function loadCalendar(month, year) {
        $('#jlayer2').load('ajax_calendar_v5.php?month=' + month + '&year=' + year);
    }

    // Photo Banner Slideshow
    (function() {
        var banner = document.getElementById('photoBanner');
        if (!banner) return;

        var imgs = banner.querySelectorAll('.pb-img');
        var dotsBox = document.getElementById('pbDots');
        var schoolEl = document.getElementById('pbSchool');
        var titleEl = document.getElementById('pbTitle');
        var dateEl = document.getElementById('pbDate');
        var total = imgs.length;
        var current = 0;
        var timer;

        if (total <= 1) return;

        // Random start
        current = Math.floor(Math.random() * total);

        // Create dots
        for (var i = 0; i < total; i++) {
            var d = document.createElement('button');
            d.className = 'dot' + (i === current ? ' active' : '');
            d.setAttribute('aria-label', 'Photo ' + (i + 1));
            (function(idx) {
                d.addEventListener('click', function() {
                    goTo(idx);
                });
            })(i);
            dotsBox.appendChild(d);
        }

        function showSlide(idx) {
            for (var j = 0; j < total; j++) {
                imgs[j].classList.toggle('active', j === idx);
            }
            var dots = dotsBox.querySelectorAll('.dot');
            for (var j = 0; j < dots.length; j++) {
                dots[j].classList.toggle('active', j === idx);
            }
            var img = imgs[idx];
            schoolEl.textContent = img.dataset.school || '';
            titleEl.textContent = img.dataset.title || '';
            dateEl.textContent = img.dataset.date || '';
        }

        function goTo(idx) {
            current = idx;
            showSlide(current);
            resetTimer();
        }

        function next() {
            current = (current + 1) % total;
            showSlide(current);
        }

        function resetTimer() {
            clearInterval(timer);
            timer = setInterval(next, 6000);
        }

        showSlide(current);
        resetTimer();
    })();

    // Mobile nav dropdown toggle
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 767) {
            var dropdownLink = e.target.closest('.nav-dropdown > a');
            if (dropdownLink) {
                e.preventDefault();
                var menu = dropdownLink.nextElementSibling;
                if (menu) {
                    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
                }
            }
        }
    });
    </script>

</body>

</html>