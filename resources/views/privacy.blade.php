@extends('layouts.app')

@section('title', 'Privacy Policy — Trastly')

@section('content')
    <div class="cosmic-page-section">
        <div class="container" style="max-width:720px;position:relative;z-index:1;padding:2rem 1rem 3rem;">
            <h1 style="color:#fff;font-size:1.75rem;font-weight:700;margin-bottom:1.5rem;">
                Privacy Policy
            </h1>
            @auth
                <div style="color:rgba(255,255,255,0.8);font-size:0.9375rem;line-height:1.7;">
                    <p><strong>Last updated:</strong> June 9, 2026</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">1. Introduction</h3>
                    <p>Trastly ("we", "our", "us") operates the website trastly.org and the Trastly UTM Builder & Link Shortener browser extension. This Privacy Policy explains how we handle your information.</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">2. Information We Collect</h3>
                    <p><strong>Website:</strong> When you register, we collect your email address and name. Payment information is processed by third-party providers (YooKassa) and is not stored on our servers.</p>

                    <p><strong>Browser Extension:</strong> The extension reads the URL and title of your active browser tab solely to auto-fill the UTM builder form. All settings, presets, and link history are stored locally in your browser using chrome.storage.local. No browsing data is transmitted to our servers.</p>

                    <p>The only network request the extension makes is when you explicitly click "Shorten with Trastly" — this sends the generated UTM URL to the Trastly API to create a short link.</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">3. How We Use Information</h3>
                    <ul>
                        <li>To provide link shortening and UTM building services</li>
                        <li>To manage your account and balance</li>
                        <li>To process payments</li>
                    </ul>

                    <h3 style="color:#fff;margin-top:1.5rem;">4. Data Sharing</h3>
                    <p>We do not sell, trade, or share your personal data with third parties, except as required by law or to process payments through our payment providers.</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">5. Data Storage</h3>
                    <p>Website data is stored on secure servers. Extension data is stored locally in your browser and never leaves your device unless you initiate a link shortening request.</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">6. Cookies</h3>
                    <p>We use essential cookies for authentication and session management. We may use analytics cookies (Yandex Metrika, Google Analytics) to understand site usage.</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">7. Your Rights</h3>
                    <p>You can delete your account and all associated data by contacting us. Extension data can be cleared by removing the extension from your browser.</p>

                    <h3 style="color:#fff;margin-top:1.5rem;">8. Contact</h3>
                    <p>
                        For privacy questions, contact us at
                        <a href="mailto:support@trastly.org" style="color:#a78bfa;">
                            support@trastly.org
                        </a>
                    </p>
                </div>
            @endauth


            @guest
                <h2 style="color:#fff;font-size:1.35rem;font-weight:700;margin-bottom:0.75rem;">
                    Политика конфиденциальности
                </h2>

                <div style="color:rgba(255,255,255,0.8);font-size:0.9375rem;line-height:1.7;">

                    <p><strong>Дата вступления в силу: 7 октября 2026 года</strong></p>

                    <p>
                        Настоящая Политика конфиденциальности регулирует сбор,
                        использование, хранение и защиту информации Пользователей Сервиса.
                    </p>


                    <h3 style="color:#fff;margin-top:1.5rem;">1. Общие положения</h3>

                    <p><strong>1.1.</strong> Настоящая Политика конфиденциальности (далее — «Политика») регулирует порядок обработки и защиты информации, которую Пользователь передаёт при использовании Сервиса.</p>

                    <p><strong>1.2.</strong> Используя Сервис, Пользователь подтверждает, что ознакомился с настоящей Политикой и принимает её условия.</p>

                    <p><strong>1.3.</strong> Если Пользователь не согласен с условиями настоящей Политики, он обязан прекратить использование Сервиса.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">2. Сбор информации</h3>

                    <p><strong>2.1.</strong> При использовании Сервиса может осуществляться сбор следующих категорий информации:</p>

                    <ul>
                        <li>идентификаторы аккаунта, включая логин, ID, никнейм и иные аналогичные данные;</li>
                        <li>техническая информация, включая IP-адрес, сведения о браузере, устройстве и операционной системе;</li>
                        <li>история взаимодействия Пользователя с Сервисом;</li>
                        <li>информация, необходимая для обработки платежей и предоставления услуг.</li>
                    </ul>

                    <p><strong>2.2.</strong> Сервис не требует предоставления паспортных данных, документов, фотографий или иной личной информации, за исключением случаев, когда такая информация необходима для исполнения обязательств, соблюдения требований законодательства или требований платёжных провайдеров.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">3. Использование информации</h3>

                    <p><strong>3.1.</strong> Полученная информация может использоваться для:</p>

                    <ul>
                        <li>обеспечения работы Сервиса и его функционала;</li>
                        <li>выполнения заказов и предоставления приобретённых услуг;</li>
                        <li>связи с Пользователем;</li>
                        <li>предоставления технической поддержки;</li>
                        <li>обработки обращений и заявок на возврат;</li>
                        <li>предотвращения злоупотреблений и мошеннических действий;</li>
                        <li>анализа и улучшения качества работы Сервиса;</li>
                        <li>выполнения требований законодательства.</li>
                    </ul>


                    <h3 style="color:#fff;margin-top:1.5rem;">4. Передача информации третьим лицам</h3>

                    <p><strong>4.1.</strong> Администрация не передаёт информацию Пользователя третьим лицам, за исключением случаев:</p>

                    <ul>
                        <li>когда это необходимо для исполнения обязательств перед Пользователем;</li>
                        <li>когда информация необходима платёжным системам, банкам и другим организациям, участвующим в обработке платежа;</li>
                        <li>когда передача требуется в соответствии с законодательством;</li>
                        <li>когда Пользователь самостоятельно предоставил согласие на такую передачу.</li>
                    </ul>

                    <p><strong>4.2.</strong> Третьим лицам передаётся только тот объём информации, который необходим для соответствующей цели.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">5. Хранение и защита данных</h3>

                    <p><strong>5.1.</strong> Информация хранится в течение срока, необходимого для достижения целей её обработки или выполнения требований законодательства.</p>

                    <p><strong>5.2.</strong> Администрация принимает разумные организационные и технические меры для защиты информации от неправомерного доступа, изменения, раскрытия, уничтожения или утраты.</p>

                    <p><strong>5.3.</strong> Несмотря на принимаемые меры безопасности, Администрация не может гарантировать абсолютную безопасность информации при её передаче через сеть Интернет.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">6. Права Пользователя</h3>

                    <p><strong>6.1.</strong> Пользователь вправе обратиться в службу поддержки по вопросам, связанным с обработкой его информации.</p>

                    <p><strong>6.2.</strong> Пользователь может запросить уточнение или исправление предоставленной им информации, если это технически возможно и не противоречит требованиям законодательства.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">7. Ответственность</h3>

                    <p><strong>7.1.</strong> Пользователь понимает, что передача информации через Интернет может быть связана с определёнными рисками.</p>

                    <p><strong>7.2.</strong> Администрация не несёт ответственности за действия третьих лиц, которые получили доступ к информации вследствие обстоятельств, находящихся вне разумного контроля Администрации.</p>

                    <p><strong>7.3.</strong> Пользователь несёт ответственность за сохранность данных доступа к своему аккаунту и не должен передавать их третьим лицам.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">8. Изменение Политики</h3>

                    <p><strong>8.1.</strong> Администрация вправе вносить изменения в настоящую Политику конфиденциальности.</p>

                    <p><strong>8.2.</strong> Актуальная редакция Политики публикуется в Сервисе.</p>

                    <p><strong>8.3.</strong> Продолжение использования Сервиса после вступления изменений в силу означает ознакомление Пользователя с актуальной редакцией Политики.</p>


                    <h3 style="color:#fff;margin-top:1.5rem;">9. Контактная информация</h3>

                    <p><strong>9.1.</strong> По вопросам, связанным с обработкой персональных данных и настоящей Политикой конфиденциальности, Пользователь может обратиться в службу поддержки через форму связи, доступную в Сервисе.</p>

                </div>
            @endguest

        </div>
    </div>
@endsection
