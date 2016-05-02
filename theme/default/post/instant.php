<!doctype html>
<html lang="en" prefix="op: http://media.facebook.com/op#">
  <head>
    <meta charset="utf-8">
    <link rel="canonical" href="<?= base_url($post->page) ?>">
    <meta property="op:markup_version" content="v1.0">
  </head>
  <body>
    <article>
      <header>
        <h1><?= $post->title->clean(); ?></h1>
        <time class="op-published" datetime="<?= $post->published->format('c') ?>"><?= $post->published->format('M d, Y') ?></time>
        <?php if($post->published->time != $post->updated->time): ?>
        <time class="op-modified" dateTime="<?= $post->updated->format('c') ?>"><?= $post->updated->format('M d, Y') ?></time>
        <?php endif; ?>
        
        <address>
          <a rel="facebook" href="#"><?= $post->user->fullname ?></a>
          <?= $post->user->about ?>
        </address>
        
        <figure>
          <img src="<?= $post->cover ?>" />
          <?php if($post->cover_label): ?>
          <figcaption><?= $post->cover_label ?></figcaption>
          <?php endif; ?>
        </figure>   

      </header>

      <?= $post->content ?>
      
     <figure class="op-ad">
        <iframe width="320" height="50" style="border:0; margin:0;" src="https://www.facebook.com/adnw_request?placement=1738038036454020_1738038136454010&amp;adtype=banner320x50"></iframe>
     </figure>

      <figure class="op-tracker">
          <iframe src="<?= base_url($post->page) ?>?utm_source=instant&amp;utm_medium=cmp&amp;utm_campaign=instant"></iframe>
      </figure>

      <footer>
        <aside>Semua kontent dalam halaman ini adalah alternatif instant artikel untuk artikel yang sudah ada di website resmi MrSeru</aside>
        <small>&copy; 2015 <?= ci()->setting->item('site_name') ?></small>
      </footer>
    </article>
  </body>
</html>