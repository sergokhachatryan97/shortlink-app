@extends('layouts.app')

@section('title', 'Terms & Conditions — Trastly')

@section('content')
<div class="cosmic-page-section">
    <div class="container" style="max-width:780px;position:relative;z-index:1;padding:2rem 1rem 3rem;">

        <h1 style="color:#fff;font-size:1.75rem;font-weight:700;margin-bottom:0.5rem;">Terms & Conditions</h1>
        <p style="color:rgba(255,255,255,0.5);font-size:0.875rem;margin-bottom:2rem;">Last updated: June 18, 2026</p>

        <div class="terms-content">

            {{-- ===== ACCEPTABLE USE POLICY ===== --}}
            <h2>Acceptable Use Policy</h2>

            <h3>1. Prohibited Uses</h3>
            <p>Users shall not use the Service for the following purposes:</p>
            <ul>
                <li>Publishing, distributing, promoting or linking to content containing child sexual abuse material, terrorist propaganda, violent extremism, copyright-infringing content, or any content deemed illegal under applicable law.</li>
                <li>Phishing, fraud, identity impersonation, malware distribution, unauthorized access to third-party systems, or any other malicious activity.</li>
                <li>Using Trastly links to intentionally bypass moderation, compliance or security mechanisms of advertising platforms, social media platforms, browsers or other third-party services, where such actions pose risks to Trastly's infrastructure, domains, reputation or operations, including but not limited to domain blacklisting, abuse complaints or service restrictions.</li>
            </ul>

            <h3>2. Account Suspension & Termination</h3>
            <p>Trastly reserves the right to suspend, restrict or permanently terminate a user account at its sole discretion and without prior notice if:</p>
            <ul>
                <li>The user violates this Acceptable Use Policy;</li>
                <li>The user's activities may negatively affect the security, stability, reputation or operability of the Service;</li>
                <li>The user's activities create legal, regulatory, financial or operational risks for Trastly.</li>
            </ul>
            <p>In such cases, all previously paid fees are non-refundable.</p>

            <h3>3. Monitoring & Compliance</h3>
            <p>To ensure compliance with this Policy, Trastly reserves the right to inspect and monitor the destination URLs and landing pages of links created through the Service.</p>
            <p>If prohibited content or activities are detected, the relevant account may be immediately suspended or permanently banned.</p>

            {{-- ===== REFUND POLICY ===== --}}
            <h2>Refund Policy</h2>

            <h3>Violations</h3>
            <p>No refunds will be issued if an account is suspended, restricted or terminated due to violations of the Acceptable Use Policy, Terms of Service or applicable law.</p>

            <h3>Voluntary Subscription Cancellation</h3>
            <p>Where permitted by the relevant subscription plan, users who voluntarily cancel a paid subscription without any violations may request a pro-rata refund for the unused service period, less payment processing fees and related administrative costs.</p>

            <h3>Free Access</h3>
            <p>Free plans, trial periods, promotional credits, promo codes and other promotional offers are not eligible for refund or compensation.</p>

            {{-- ===== WHITE-LABEL DISCLAIMER ===== --}}
            <h2>White-Label Partner Disclaimer</h2>
            <p>Partners using the Trastly White-Label API bear full and sole responsibility for all content, destination pages, links and user activities associated with their integration.</p>
            <p>Partners agree to include in their own terms of service and acceptable use policies restrictions substantially equivalent to those set forth in this document.</p>
            <p>Trastly reserves the right to restrict, suspend or terminate API access without refund if a Partner's or its customers' activities violate this Policy or create material risk to Trastly's infrastructure, domains, reputation or operations.</p>

            <div class="terms-divider"></div>

            {{-- ===== CHINESE VERSION ===== --}}
            <h2>可接受使用政策（Acceptable Use Policy）</h2>

            <h3>1. 禁止用途</h3>
            <p>用户不得将本服务用于以下用途：</p>
            <ul>
                <li>发布、传播、推广或链接至包含儿童性虐待内容、恐怖主义宣传、暴力极端主义内容、侵犯版权内容，或根据适用法律被认定为非法的任何内容。</li>
                <li>网络钓鱼（Phishing）、诈骗、身份冒充、恶意软件传播、未经授权访问第三方系统，或任何其他恶意活动。</li>
                <li>利用 Trastly 链接故意绕过广告平台、社交媒体平台、浏览器或其他第三方服务的审核、合规或安全机制，并因此对 Trastly 的基础设施、域名、声誉或运营造成风险，包括但不限于域名被列入黑名单、收到大量滥用投诉或受到服务限制。</li>
            </ul>

            <h3>2. 账户暂停与终止</h3>
            <p>在以下情况下，Trastly 有权自行决定暂停、限制或永久终止用户账户，而无需事先通知：</p>
            <ul>
                <li>用户违反本可接受使用政策；</li>
                <li>用户从事可能影响服务安全性、稳定性、声誉或正常运营的活动；</li>
                <li>用户给 Trastly 带来法律、监管、财务或运营风险。</li>
            </ul>
            <p>在上述情况下，用户已支付的所有费用均不予退还。</p>

            <h3>3. 监控与合规检查</h3>
            <p>为确保遵守本政策，Trastly 有权对用户创建链接所指向的目标网址和落地页进行检查与监控。</p>
            <p>如发现存在被禁止的内容或活动，相关账户可能被立即暂停或永久封禁。</p>

            <h2>退款政策（Refund Policy）</h2>

            <h3>违规情况</h3>
            <p>若账户因违反可接受使用政策、服务条款或适用法律而被暂停、限制或终止，已支付费用概不退还。</p>

            <h3>主动取消订阅</h3>
            <p>在相关订阅方案允许的情况下，用户主动取消付费订阅且不存在违规行为时，可根据未使用的服务期限按比例申请退款，但需扣除支付渠道手续费及相关管理费用。</p>

            <h3>免费试用</h3>
            <p>免费方案、促销赠金、试用期以及奖励余额均不享有退款或补偿权利。</p>

            <h3>White-Label 合作伙伴免责声明</h3>
            <p>使用 Trastly White-Label API 的合作伙伴应对其生成链接所涉及的全部内容、目标地址及相关活动承担全部责任。</p>
            <p>合作伙伴同意在其自身的服务条款及可接受使用政策中纳入与本政策实质相同的限制条款。</p>
            <p>若合作伙伴或其客户从事被禁止的活动，或对 Trastly 的基础设施、域名、声誉或业务运营造成重大风险，Trastly 有权暂停、限制或终止其 API 访问权限，且不退还已支付费用。</p>

            <div class="terms-divider"></div>

            {{-- ===== RUSSIAN VERSION ===== --}}
            <h2>Политика допустимого использования</h2>

            <h3>1. Запрещённые виды использования</h3>
            <p>Пользователь не вправе использовать Сервис для следующих целей:</p>
            <ul>
                <li>Размещения, распространения, продвижения либо перенаправления на ресурсы, содержащие материалы сексуального насилия над детьми, террористический контент, пропаганду насилия и экстремизма, материалы, нарушающие авторские права, а также любой иной контент, признанный незаконным в соответствии с применимым законодательством.</li>
                <li>Фишинга, мошенничества, подмены личности, распространения вредоносного программного обеспечения, несанкционированного доступа к информационным системам третьих лиц и иных противоправных действий.</li>
                <li>Использования ссылок Trastly для преднамеренного обхода механизмов модерации, комплаенса или безопасности рекламных сетей, социальных платформ, браузеров и иных сторонних сервисов в случаях, когда такие действия создают риск для инфраструктуры, доменов, репутации или деятельности Trastly.</li>
            </ul>

            <h3>2. Приостановление и прекращение обслуживания</h3>
            <p>Компания Trastly вправе по собственному усмотрению временно ограничить, приостановить или полностью прекратить доступ Пользователя к Сервису без предварительного уведомления в случаях, если:</p>
            <ul>
                <li>Пользователь нарушает настоящую Политику допустимого использования;</li>
                <li>Действия Пользователя могут негативно повлиять на безопасность, стабильность, репутацию или работоспособность Сервиса;</li>
                <li>Действия Пользователя создают юридические, регуляторные, финансовые либо операционные риски для Trastly.</li>
            </ul>
            <p>В указанных случаях все ранее внесённые платежи считаются невозвратными.</p>

            <h3>3. Мониторинг и проверка</h3>
            <p>В целях соблюдения настоящей Политики Trastly вправе осуществлять мониторинг, анализ и проверку целевых страниц и ресурсов, на которые ведут ссылки, созданные через Сервис.</p>
            <p>При выявлении запрещённого контента либо запрещённой деятельности соответствующая учётная запись может быть немедленно ограничена или заблокирована.</p>

            <h2>Политика возврата средств</h2>

            <h3>Нарушение правил</h3>
            <p>Возврат денежных средств не производится в случае ограничения, приостановления либо блокировки учётной записи вследствие нарушения Политики допустимого использования, Условий предоставления услуг либо применимого законодательства.</p>

            <h3>Добровольная отмена подписки</h3>
            <p>Если это предусмотрено соответствующим тарифным планом, Пользователь вправе запросить возврат денежных средств за неиспользованный период подписки при добровольном отказе от услуги, при условии отсутствия нарушений со стороны Пользователя.</p>
            <p>Размер возврата рассчитывается пропорционально неиспользованному периоду обслуживания за вычетом комиссий платёжных систем и иных сопутствующих расходов.</p>

            <h3>Бесплатный доступ</h3>
            <p>Бесплатные тарифы, пробные периоды, бонусные начисления, промокоды и иные рекламные предложения не подлежат компенсации или возврату.</p>

            <h3>Отказ от ответственности для White-Label партнёров</h3>
            <p>Партнёры, использующие White-Label API Trastly, несут самостоятельную и полную ответственность за весь контент, целевые страницы, ссылки и действия пользователей, связанные с использованием их интеграции.</p>
            <p>Партнёр обязуется включить в собственные пользовательские соглашения и политики использования ограничения, аналогичные предусмотренным настоящими документами.</p>
            <p>Компания Trastly вправе ограничить, приостановить либо прекратить доступ к API без возврата денежных средств в случае, если действия Партнёра либо его клиентов нарушают настоящую Политику или создают существенный риск для инфраструктуры, доменов, репутации либо деятельности Trastly.</p>

        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.terms-content { color: rgba(255,255,255,0.8); font-size: 0.9375rem; line-height: 1.8; }
.terms-content h2 { color: #fff; font-size: 1.35rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(167,139,250,0.2); }
.terms-content h3 { color: #c4b5fd; font-size: 1rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.5rem; }
.terms-content p { margin-bottom: 0.75rem; }
.terms-content ul { padding-left: 1.25rem; margin-bottom: 1rem; }
.terms-content li { margin-bottom: 0.5rem; }
.terms-divider { height: 2px; background: linear-gradient(90deg, rgba(167,139,250,0.3), transparent); margin: 3rem 0; }
</style>
@endpush
