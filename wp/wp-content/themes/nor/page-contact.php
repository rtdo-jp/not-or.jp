<?php get_header(); ?>

<?php
  $page_id = get_queried_object_id();

  $title = get_the_title($page_id);
  $tagline = nor_get_post_meta_text((int) $page_id, 'nor_tagline');
  $desc_ja = nor_get_post_meta_text((int) $page_id, 'nor_desc_ja');
  $desc_en = nor_get_post_meta_text((int) $page_id, 'nor_desc_en');

  $title   = is_string($title) ? trim($title) : '';
  $tagline = is_string($tagline) ? trim($tagline) : '';
  $desc_ja = is_string($desc_ja) ? trim($desc_ja) : '';
  $desc_en = is_string($desc_en) ? trim($desc_en) : '';

  if ($title === '') $title = 'Contact';
  if ($tagline === '') $tagline = '—';
  if ($desc_ja === '') $desc_ja = '—';
  if ($desc_en === '') $desc_en = '—';

  $render_inline = static function (string $raw, string $fallback = ''): string {
    return nor_render_inline_html($raw, $fallback);
  };

  $contact_status = isset($_GET['contact_status']) ? sanitize_key((string) wp_unslash($_GET['contact_status'])) : '';
  $announcement_state = '';
  $error_ja = '入力内容を御確認のうえ、必要事項を入力してください。';
  $error_en = 'Please review your entries.';
  $success_ja = 'お問い合わせを送信しました。';
  $success_en = 'Your message has been sent.';

  if ($contact_status === 'success') {
    $announcement_state = 'is-success';
  } elseif ($contact_status === 'duplicate') {
    $announcement_state = 'is-error';
    $error_ja = '同じ内容の送信が短時間に続いたため、送信をスキップしました。時間をおいて再度お試しください。';
    $error_en = 'A similar submission was received recently. Please try again later.';
  } elseif ($contact_status === 'spam') {
    $announcement_state = 'is-error';
    $error_ja = '送信内容を受け付けられませんでした。時間をおいて再度お試しください。';
    $error_en = 'Your submission could not be accepted. Please try again later.';
  } elseif ($contact_status === 'error') {
    $announcement_state = 'is-error';
  }

  $form_started_at = time();
  $form_started_sig = nor_contact_started_signature($form_started_at);
  $form_action = admin_url('admin-post.php');
  $policies_url = esc_url(home_url('/policies/'));
  $privacy_policy_url = esc_url(home_url('/policies/#policies-heading-privacy'));

  $announcement_class = 'announcement';
  if ($announcement_state !== '') {
    $announcement_class .= ' ' . $announcement_state;
  }

  $announcement_attrs = '';
  if ($announcement_state === 'is-error') {
    $announcement_attrs = ' role="alert" aria-live="assertive" aria-atomic="true"';
  } elseif ($announcement_state === 'is-success') {
    $announcement_attrs = ' role="status" aria-live="polite" aria-atomic="true"';
  }
?>
    <aside class="section contact-sidebar" aria-labelledby="contact-title">
      <div class="inner">
        <div class="identity">
          <div class="name">
            <p class="ja" lang="ja">田村綾佑デザイン事務所</p>
            <p class="en" lang="en">Ryousuke Tamura <br>Design Office</p>
            <p class="author">nør.</p>
          </div>
        </div>

        <div class="localize">
          <div class="specific">
            <h1 id="contact-title"><?php echo esc_html($title); ?></h1>
            <p class="tagline"><?php echo $render_inline($tagline); ?></p>
          </div>
          <div class="textpair">
            <p class="ja" lang="ja"><?php echo $render_inline($desc_ja); ?></p>
            <p class="en" lang="en"><?php echo $render_inline($desc_en); ?></p>
          </div>
        </div>

        <section class="notes">
          <div class="title">
            <h2 id="notes-before-sending" lang="en"><span class="character-line">Notes before sending</span></h2>
          </div>
          <div class="main">
            <div class="ja" lang="ja">
              <p><em><span aria-hidden="true">*</span>は必須項目です。</em><br>日本語での御連絡を推奨しています（英語も受け付けています）。すべてのお問い合わせに御返信できない場合があります。<br class="desktop tablet">また、初めて御連絡いただく場合は、対面での御挨拶又は御紹介経由を前提としています。</p>
            </div>
            <div class="en" lang="en">
              <p><em><span aria-hidden="true">*</span> indicates required fields.</em><br>Japanese is preferred (English is accepted). nør. may not be able to reply to every message.<br class="desktop tablet">Advertising or sales emails are not accepted. For first-time inquiries, nør. accepts contact in person or via referral only.</p>
            </div>
          </div>
          <ul class="actions">
            <li><a href="<?php echo esc_url(home_url('/')); ?>" class="btn">Back to Home</a></li>
            <li><a href="<?php echo esc_url(home_url('/policies/')); ?>" class="btn">Go to Policies</a></li>
          </ul>
        </section>
      </div>
    </aside>

  </div>

  <main id="site-main" class="contact-main" tabindex="-1">
    <section class="section contact-content">
      <h2 class="visually-hidden">Contact form</h2>
      <div class="inner">
        <div class="<?php echo esc_attr($announcement_class); ?>"<?php echo $announcement_attrs; ?>>
          <div class="message is-error"<?php echo ($announcement_state === 'is-error') ? '' : ' hidden'; ?>>
            <p class="ja" lang="ja"><?php echo esc_html($error_ja); ?></p>
            <p class="en" lang="en"><?php echo esc_html($error_en); ?></p>
          </div>

          <div class="message is-success"<?php echo ($announcement_state === 'is-success') ? '' : ' hidden'; ?>>
            <p class="ja" lang="ja"><?php echo esc_html($success_ja); ?></p>
            <p class="en" lang="en"><?php echo esc_html($success_en); ?></p>
          </div>
        </div>

        <form class="form contact-form" action="<?php echo esc_url($form_action); ?>" method="post" novalidate>
          <input type="hidden" name="action" value="nor_contact_submit">
<?php
            ob_start();
            wp_nonce_field('nor_contact_submit', 'nor_contact_nonce');
            $nonce_html = trim((string) ob_get_clean(), "\r\n");
            $nonce_html = (string) preg_replace('/>\s*<input\b/i', ">\n<input", $nonce_html);
            echo (string) preg_replace('/^(?=.*\S)/m', '          ', $nonce_html) . "\n";
?>
          <input type="hidden" name="_nor_contact_started_at" value="<?php echo esc_attr((string) $form_started_at); ?>">
          <input type="hidden" name="_nor_contact_started_sig" value="<?php echo esc_attr($form_started_sig); ?>">

          <div class="field visually-hidden" aria-hidden="true">
            <label for="_nor_contact_website">Website</label>
            <input id="_nor_contact_website" name="_nor_contact_website" type="text" tabindex="-1" autocomplete="off">
          </div>

        <fieldset class="field">
          <legend class="field-label">
            <span class="ja" lang="ja">用件の選択<span aria-hidden="true">*</span></span><br>
            <span class="en" lang="en">Purpose<span aria-hidden="true">*</span></span>
          </legend>

          <div class="choices">
            <label class="choice">
              <input id="purpose-publication" name="purpose" type="radio" value="publication" aria-describedby="purpose-error">
              <span class="choice-body">
                <span class="choice-title">
                  <span class="ja" lang="ja">掲載・訂正の依頼</span>
                  <span class="en" lang="en">Publication or Correction</span>
                </span>
                <span class="textpair">
                  <span class="ja" lang="ja">掲載内容の追加・修正の要望。根拠 <abbr title="Uniform Resource Locator">URL</abbr> や正式表記を明記。</span>
                  <span class="en" lang="en">Requests to add or update facts. Include source <abbr title="Uniform Resource Locator">URL</abbr>s and official spellings.</span>
                </span>
              </span>
            </label>

            <label class="choice">
              <input id="purpose-takedown" name="purpose" type="radio" value="takedown" aria-describedby="purpose-error">
              <span class="choice-body">
                <span class="choice-title">
                  <span class="ja" lang="ja">削除依頼</span>
                  <span class="en" lang="en">Takedown</span>
                </span>
                <span class="textpair">
                  <span class="ja" lang="ja">権利・契約・機密などの理由で非公開化。対象 <abbr title="Uniform Resource Locator">URL</abbr> と理由を明記。</span>
                  <span class="en" lang="en">Removal due to rights, contracts, or confidentiality. Provide <abbr title="Uniform Resource Locator">URL</abbr>s and reasons.</span>
                </span>
              </span>
            </label>

            <label class="choice">
              <input id="purpose-rights" name="purpose" type="radio" value="rights" aria-describedby="purpose-error">
              <span class="choice-body">
                <span class="choice-title">
                  <span class="ja" lang="ja">権利・商標など</span>
                  <span class="en" lang="en">Rights &amp; Trademarks</span>
                </span>
                <span class="textpair">
                  <span class="ja" lang="ja">著作権・商標・クレジット表記の更新。希望表記と根拠を明記。</span>
                  <span class="en" lang="en">Copyright, trademark, or credit updates. State requested wording and basis.</span>
                </span>
              </span>
            </label>

            <label class="choice">
              <input id="purpose-tech" name="purpose" type="radio" value="technical" aria-describedby="purpose-error">
              <span class="choice-body">
                <span class="choice-title">
                  <span class="ja" lang="ja">技術的不具合</span>
                  <span class="en" lang="en">Technical Issue</span>
                </span>
                <span class="textpair">
                  <span class="ja" lang="ja">表示崩れ・リンク切れ・誤記など。環境情報（<abbr title="Operating System">OS</abbr>/ブラウザ/<abbr title="Uniform Resource Locator">URL</abbr>/時刻）添付推奨。</span>
                  <span class="en" lang="en">Rendering issues, broken links, or typos. Include environment details (<abbr title="Operating System">OS</abbr>/Browser/<abbr title="Uniform Resource Locator">URL</abbr>/Time).</span>
                </span>
              </span>
            </label>

            <label class="choice">
              <input id="purpose-other" name="purpose" type="radio" value="other" aria-describedby="purpose-error">
              <span class="choice-body">
                <span class="choice-title">
                  <span class="ja" lang="ja">その他（返信できない場合あり）</span>
                  <span class="en" lang="en">Other (reply not guaranteed)</span>
                </span>
              </span>
            </label>
          </div>

          <p id="purpose-error" class="field-message is-error">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">⚠️</span>用件の選択は必須項目です。</span>
            <span class="en" lang="en">Purpose is required.</span>
          </p>
        </fieldset>

        <div class="field">
          <label class="field-label" for="name">
            <span class="ja" lang="ja">お名前<span aria-hidden="true">*</span></span><br>
            <span class="en" lang="en">Name<span aria-hidden="true">*</span></span>
          </label>
          <div class="control">
            <input id="name" name="name" type="text" autocomplete="name" required placeholder="お名前を入力 / Your full name." aria-describedby="name-error">
            <button class="btn btn-clear" type="button" aria-label="Clear"><span class="btn-glyph" aria-hidden="true">✕</span></button>
          </div>
          <p id="name-error" class="field-message is-error">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">⚠️</span>お名前は必須です。</span>
            <span class="en" lang="en">Name is required.</span>
          </p>
        </div>

        <div class="field">
          <label class="field-label" for="organization">
            <span class="ja" lang="ja">御所属<span aria-hidden="true">*</span></span><br>
            <span class="en" lang="en">Organization<span aria-hidden="true">*</span></span>
          </label>
          <div class="control">
            <input id="organization" name="organization" type="text" autocomplete="organization" required placeholder="会社・組織名を入力 / Company or organization." aria-describedby="organization-error">
            <button class="btn btn-clear" type="button" aria-label="Clear"><span class="btn-glyph" aria-hidden="true">✕</span></button>
          </div>
          <p id="organization-error" class="field-message is-error">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">⚠️</span>御所属は必須です。</span>
            <span class="en" lang="en">Organization is required.</span>
          </p>
        </div>

        <div class="field">
          <label class="field-label" for="email">
            <span class="ja" lang="ja">メールアドレス<span aria-hidden="true">*</span></span><br>
            <span class="en" lang="en">Email<span aria-hidden="true">*</span></span>
          </label>
          <div class="control">
            <input id="email" name="email" type="email" autocomplete="email" required placeholder="メールアドレスを入力 / Email address." aria-describedby="email-info email-success email-error">
            <button class="btn btn-clear" type="button" aria-label="Clear"><span class="btn-glyph" aria-hidden="true">✕</span></button>
          </div>

          <p id="email-info" class="field-message has-info">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">ℹ️</span>半角英数字で入力してください。</span>
            <span class="en" lang="en">Use ASCII characters.</span>
          </p>
          <p id="email-success" class="field-message is-success">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">🙆‍♂️</span>メールアドレスの形式に問題ありません。</span>
            <span class="en" lang="en">Looks good.</span>
          </p>
          <p id="email-error" class="field-message is-error">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">⚠️</span>メールアドレスを入力し、形式を御確認ください。</span>
            <span class="en" lang="en">Enter a valid email address.</span>
          </p>
        </div>

        <div class="field">
          <label class="field-label" for="urls">
            <span class="ja" lang="ja">対象URL<span aria-hidden="true">*</span></span><br>
            <span class="en" lang="en">Relevant URL(s)<span aria-hidden="true">*</span></span>
          </label>
          <div class="control">
            <input id="urls" name="urls" type="url" inputmode="url" autocomplete="url" required placeholder="対象URLを入力 / Relevant URL(s)." aria-describedby="urls-info-1 urls-info-2 urls-success urls-error">
            <button class="btn btn-clear" type="button" aria-label="Clear"><span class="btn-glyph" aria-hidden="true">✕</span></button>
          </div>

          <p id="urls-info-1" class="field-message has-info">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">ℹ️</span>半角英数字で入力してください。</span>
            <span class="en" lang="en">Use valid URLs (multiple allowed).</span>
          </p>
          <p id="urls-info-2" class="field-message has-info">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">ℹ️</span>特定できない場合はトップページURLでも可。</span>
            <span class="en" lang="en">If unsure, paste the top page URL.</span>
          </p>
          <p id="urls-success" class="field-message is-success">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">🙆‍♂️</span>URLの形式に問題ありません。</span>
            <span class="en" lang="en">Looks good.</span>
          </p>
          <p id="urls-error" class="field-message is-error">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">⚠️</span>対象URLを入力し、形式を御確認ください。</span>
            <span class="en" lang="en">Enter valid URL(s).</span>
          </p>
        </div>

        <div class="field">
          <label class="field-label" for="details">
            <span class="ja" lang="ja">用件の詳細</span><br>
            <span class="en" lang="en">Details</span>
          </label>
          <div class="control is-textarea">
            <textarea id="details" name="details" maxlength="400" placeholder="400文字以内で入力 / Max 400 characters." aria-describedby="details-error"></textarea>
          </div>
          <p id="details-error" class="field-message is-error">
            <span class="ja" lang="ja"><span class="mark" aria-hidden="true">⚠️</span>400文字以内で入力してください。</span>
            <span class="en" lang="en">Please keep within 400 characters.</span>
          </p>
        </div>

        <div class="notes">
          <div class="textpair">
            <p class="ja" lang="ja">送信により、当サイトの <i>Policies</i>（プライバシー等）に同意したものとみなします。<br>御記入いただいた情報は、本サイトの運用及びお問い合わせへの対応のためにのみ利用します。詳細は「<a href="<?php echo $policies_url; ?>"><i>Policies</i></a>」&gt;「<a href="<?php echo $privacy_policy_url; ?>"><i>Privacy</i></a>」を御確認ください。</p>
            <p class="en" lang="en">By submitting, you agree to our <i>Policies</i> (including privacy).<br>The information you provide will be used solely for operating this site and responding to your inquiry. For details, see “<a href="<?php echo $policies_url; ?>"><i>Policies</i></a>” &gt; “<a href="<?php echo $privacy_policy_url; ?>"><i>Privacy</i></a>”.</p>
          </div>
        </div>

        <div class="actions">
          <button class="btn" type="submit">Submit</button>
        </div>

        <div class="notes">
          <div class="textpair">
            <p class="ja" lang="ja">送信後にフォームから内容を修正することはできません。入力内容を御確認のうえ送信してください。</p>
            <p class="en" lang="en">You cannot edit your message via this form after submission. Please review your entries before sending.</p>
          </div>
        </div>
        </form>
      </div>
    </section>
  </main>

</div>

<?php if ($contact_status === 'success') : ?>
<script>
  (function () {
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.get("contact_status") !== "success") return;

      if (typeof window.gtag === "function") {
        window.gtag("event", "contact_submit", {
          event_category: "contact",
          event_label: "contact_form",
          method: "form_submit",
          value: 1
        });
      }

      url.searchParams.delete("contact_status");
      var next = url.pathname;
      var query = url.searchParams.toString();
      if (query) next += "?" + query;
      if (url.hash) next += url.hash;
      window.history.replaceState({}, document.title, next);
    } catch (e) {}
  })();
</script>
<?php endif; ?>

<?php get_footer(); ?>
