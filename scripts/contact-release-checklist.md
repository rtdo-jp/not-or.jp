# Contact Release Checklist

## 1. robots 最終確認

1. WordPress の `設定 > 表示設定 > 検索エンジンでの表示` を本番状態にする（OFF）。
2. 以下コマンドで `robots` を確認する。

```bash
cd /path/to/not-or.jp
scripts/check-head-meta.sh --base https://not-or.jp --auth 'USER:PASS' / /contact/ /works/ /categories/ /archives/ /faqs/
```

3. `meta name="robots"` が `index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1` になっていることを確認する。
4. `canonical` と `og:url` が一致していることを確認する。

## 2. Contact 本番確認（5用件）

以下5パターンを1件ずつ送信する。

- `publication`
- `takedown`
- `rights`
- `technical`
- `other`

各送信で以下を確認する。

1. フロントの送信完了表示が出る。
2. 受信一覧（`Contact > 受信一覧`）に保存される。
3. 管理通知メールが届く（主通知先 + サブ通知先）。
4. 自動返信メールが届く（件名・本文・プレースホルダー展開）。
5. GA4 DebugView に `contact_submit` が1回記録される。
6. 返信メール送信の件名/本文テンプレートが用件に応じて初期表示される。

## 3. プレースホルダー確認

自動返信本文で以下が正しく展開されること。

- `{name}`
- `{organization}`
- `{email}`
- `{urls}`
- `{details}`
- `{purpose_ja}`
- `{site_name}`
- `{site_url}`

## 4. 運用確認

1. `Contact` 詳細画面でステータス更新が保存される。
2. 管理メモが保存される。
3. 「返信メールを送信」で送信成功し、送信履歴に残る。
4. 保持期間（指定期間（ヶ月））が想定値（24）になっている。
