@extends('layouts.login_layout')

@section('title', '利用規約 | 災図')

@section('content')
  <main class="container my-4" style="max-width: 900px;">
    <div class="card">
      <div class="card-body p-4">
        <h1 class="h3 mb-3">利用規約</h1>
        <p>この利用規約は、災害支援情報共有システム「災図」（以下「本サービス」）の利用条件を定めるものです。</p>

        <section class="mt-4" aria-labelledby="terms-purpose">
          <h2 id="terms-purpose" class="h5">1. 本サービスの目的</h2>
          <p>本サービスは、支援団体による災害情報、支援計画、日報などの共有と、支援活動の調整を目的とします。緊急通報や救助要請の受付を行うサービスではありません。</p>
        </section>

        <section class="mt-4" aria-labelledby="terms-account">
          <h2 id="terms-account" class="h5">2. 利用登録とアカウント管理</h2>
          <p>利用登録には正確な団体情報と担当者情報を入力してください。オンライン登録後は、事務局の承認を受けてから利用できます。</p>
          <p>利用者は自身のアカウントとパスワードを管理し、第三者への貸与や共有を行わないでください。不正利用が疑われる場合は、速やかに所属団体の管理者へ連絡してください。</p>
        </section>

        <section class="mt-4" aria-labelledby="terms-content">
          <h2 id="terms-content" class="h5">3. 登録する情報と共有範囲</h2>
          <p>登録する情報は、利用者が共有する権限を持つものに限ります。情報の正確性、更新日時、公開・共有範囲を確認し、誤りが分かった場合は修正してください。</p>
          <p>氏名、連絡先、健康状態など個人に関する情報は、支援活動に必要な範囲に限定し、所属団体のルールと適用される法令に従って取り扱ってください。非公開のメモについても、必要以上の個人情報や秘密情報を入力しないでください。</p>
          <p>入力した情報に関する権利は、利用者または元の権利者に帰属します。利用者は、本サービスで設定した共有範囲に応じて、情報が他の利用者に閲覧されることを確認したうえで登録してください。</p>
        </section>

        <section class="mt-4" aria-labelledby="terms-ai">
          <h2 id="terms-ai" class="h5">4. AI入力補助</h2>
          <p>AI入力補助を利用する場合、入力した情報共有会議用メモなどが、設定された外部AIサービスに送信されます。送信する権限のない情報、個人を特定できる情報、秘密情報を含めないでください。</p>
          <p>AIの提案には誤りが含まれる場合があります。利用者が内容を確認し、必要な修正を行ってから保存してください。</p>
        </section>

        <section class="mt-4" aria-labelledby="terms-prohibited">
          <h2 id="terms-prohibited" class="h5">5. 禁止事項</h2>
          <ul>
            <li>他者へのなりすまし、不正アクセス、権限のない情報の取得や公開</li>
            <li>虚偽情報の意図的な登録、支援活動や他の利用者を妨げる行為</li>
            <li>個人情報、秘密情報、著作権など他者の権利を侵害する情報の登録</li>
            <li>本サービスに過度な負荷を与える行為、悪意のあるファイルやプログラムの送信</li>
            <li>法令に違反する行為</li>
          </ul>
        </section>

        <section class="mt-4" aria-labelledby="terms-availability">
          <h2 id="terms-availability" class="h5">6. 情報の確認とサービスの中断</h2>
          <p>支援活動の判断にあたっては、本サービスの情報だけに依存せず、現地の状況や関係機関の最新情報も確認してください。</p>
          <p>保守作業、通信障害、災害などにより、本サービスを利用できない場合があります。活動に必要な連絡手段と記録は、所属団体でも確保してください。</p>
        </section>

        <section class="mt-4" aria-labelledby="terms-suspension">
          <h2 id="terms-suspension" class="h5">7. 利用の停止</h2>
          <p>規約違反やアカウントの不正利用が確認された場合、または安全確保のために必要な場合、運営者は状況に応じてアカウントの利用停止などの措置を行うことがあります。</p>
        </section>

        <section class="mt-4" aria-labelledby="terms-changes">
          <h2 id="terms-changes" class="h5">8. 利用条件の変更</h2>
          <p>利用条件を変更する場合は、変更内容と適用日を本サービス内などでお知らせします。必要な場合は、変更後の条件について改めて同意を確認します。</p>
        </section>

        <nav class="mt-4" aria-label="利用規約ページからの移動">
          @auth
            <a href="{{ route('home') }}" class="btn btn-outline-primary">ホームに戻る</a>
          @else
            <a href="{{ route('signup.create') }}" class="btn btn-primary">利用登録へ</a>
            <a href="{{ route('login') }}" class="btn btn-outline-primary">ログインへ</a>
          @endauth
        </nav>
      </div>
    </div>
  </main>
@endsection
