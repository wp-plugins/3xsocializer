<?php

    $posts_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM `" . $this->tbl_social_posts . "`"));


    $social_types = array(
        "tw" => "Twitter",
        "fb" => "Facebook",
        "ln" => "LinkedIn",
    );
    $current_page = isset($_GET['paged']) && is_numeric($_GET['paged']) ? $_GET['paged'] : 1;
    $pages_count  = $posts_count/20 > intval($posts_count/20) ? intval($posts_count/20) + 1 : intval($posts_count/20);
    $social_posts = $wpdb->get_results("SELECT id, title, network, state, status, date_format(scheduled_time,'%Y/%m/%d %r') as scheduled_time, date_format(published_time, '%Y/%m/%d %r') as published_time FROM `" . $this->tbl_social_posts . "` order by id desc limit 20 offset ".(($current_page-1)*20), ARRAY_A);

    $action_url = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],"social_posts")+12)."&paged=";
    $next_page = $current_page < $pages_count ? $current_page + 1 : null;
    $prev_page = $current_page > 1 ? $current_page - 1 : null;
?>
<div class="wrap">
    <h2>Social Posts</h2>
    <form method="POST">
    <div style="float: left" class="tablenav top">
        <div class="alignleft actions">
            <select name="bulk_action">
                <option value="-1" selected="selected">Bulk Actions</option>
                <option value="share">Share</option>
                <option value="trash">Delete</option>
            </select>
            <input type="submit" name="" id="doaction" class="button-secondary action" value="Apply">
        </div>
        <br class="clear">
    </div>
        <div class="tablenav">
        <div  style="float: right;" class="tablenav-pages"><span class="displaying-num"><?php echo $posts_count ?> items</span>
            <span class="pagination-links"><a class="first-page <?php echo $prev_page ? "" : "disabled" ?>" title="Go to the first page" href="<?php echo $action_url."1" ?>">«</a>
            <a class="prev-page <?php echo $prev_page ? "" : "disabled" ?>" title="Go to the previous page" href="<?php echo $prev_page ? $action_url.$prev_page : "" ?>">‹</a>
            <span class="paging-input"><?php echo $current_page ?> of <span class="total-pages"><?php echo $pages_count ?></span></span>
            <a class="next-page <?php echo $next_page ? "" : "disabled" ?>" title="Go to the next page" href="<?php echo $next_page ? $action_url.$next_page : "" ?>">›</a>
            <a class="last-page <?php echo $next_page ? "" : "disabled" ?>" title="Go to the last page" href="<?php echo $pages_count ? $action_url.$pages_count : "" ?>">»</a></span></div>
        </div>
    <table class="wp-list-table widefat fixed" cellspacing="0">
    <thead>
    <tr>
        <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
        <th scope="col" id="title" class="manage-column column-title" style=""><span>Title</span></a></th>
        <th scope="col" id="network" class="manage-column" style=""><span>Network</span></th>
        <th scope="col" id="status" class="manage-column" style="width: 80px"><span>Status</span></th>
        <th scope="col" id="date" class="manage-column column-date" style="width: 160px"><span>Date</span></th>
    </tr>
    </thead>

    <tbody id="the-list">


<?php foreach ($social_posts as $post): ?>
    <tr id="social-post-<?php echo $post['id'] ?>" class="post type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" valign="top">
        <th scope="row" class="check-column">
            <input type="checkbox" name="post[]" value="<?php echo $post['id'] ?>">
        </th>
        <td class="post-title page-title column-title">
            <strong>
                <?php if ($post['state'] == 'scheduled') { ?>
                <a class="row-title"
                   href="http://blog.vakoms.com/wp-admin/admin.php?page=social_posts&post=<?php echo $post['id'] ?>&amp;action=share&amp;network=<?php echo $post['network'] ?>"
                      title="Share “<?php echo $post['title'] ?>”">
                      <?php echo $post['title'] ?>
                </a>
                <?php } else { echo $post['title']; }?>
            </strong>

            <div class="row-actions">
                <span class="edit">
                    <?php if ($post['state'] == 'scheduled') { ?>
                    <a href="http://blog.vakoms.com/wp-admin/admin.php?page=social_posts&post=<?php echo $post['id'] ?>&amp;action=share&amp;network=<?php echo $post['network'] ?>"
                       title="Share this item">Share</a>
                    <?php } else { ?>
                       Already shared
                    <?php } ?>
                </span>
            </div>
        </td>
        <td class="network"><?php echo $social_types[$post['network']] ?></td>
        <?php if($post['status'] && $post['state'] == 'published') {?>
        <td style="color: green;">OK</td>
        <?php } elseif(!$post['status'] && $post['state'] == 'published') { ?>
        <td style="color: red;">FAILED</td>
        <?php } else { ?>
        <td>SCHEDULED</td>
        <?php } ?>
        <td class="date column-date"><abbr title="<?php echo $post['state'] == "published" ? $post['published_time'] : $post['scheduled_time'] ?>"><?php echo $post['state'] == "published" ? $post['published_time'] : $post['scheduled_time'] ?></abbr><br><?php echo ucfirst($post['state']) ?></td>
    </tr>
<?php endforeach ?>
    </tbody>
    </table>
    </form>
</div>