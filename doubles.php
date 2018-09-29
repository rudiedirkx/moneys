<?php

require 'inc.bootstrap.php';

$transactions = get_doubles();

$categories = $db->select_fields('categories', 'id, name', '1 ORDER BY name ASC');
Transaction::$_categories = $categories;

$tags = $db->select_fields('tags', 'id, tag', '1 ORDER BY tag ASC');
Tag::decorateTransactions($transactions, $tags);

require 'tpl.header.php';

?>
<h1>Potential doubles</h1>

<p>Add the tag <code>"undouble"</code> to ignore transactions. They won't show up here anymore.</p>
<?php

$show_pager = false;
$with_sorting = false;
$grouper = 'simple_uniq';
include 'tpl.transactions.php';

require 'tpl.footer.php';
