<!-- Cookie & Terms Consent Banner -->
<div id="cookieConsentBanner" class="cookie-consent-banner" style="display: none;">
    <div class="d-flex align-items-center gap-3">
        <i class="fas fa-cookie-bite fa-2x text-warning flex-shrink-0"></i>
        <div>
            <h6 class="mb-1 fw-bold">Cookie & Privacy Consent</h6>
            <p class="mb-0 small text-muted">
                We use essential cookies to manage secure login sessions and campus trading features. 
                <a href="data:text/plain;charset=utf-8,BAUST%20Exchange%20Cookie%20Policy%0A%0AWe%20use%20essential%20session%20cookies%20to%20authenticate%20users%20and%20remember%20UI%20preferences." download="BAUST_Exchange_Cookie_Policy.txt" class="fw-bold text-primary">Download Policy File (.txt)</a>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0 mt-2 mt-sm-0">
        <button type="button" class="btn btn-primary btn-sm px-3" onclick="acceptCookieConsent()">Accept & Continue</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!localStorage.getItem('cookie_consent_accepted')) {
            document.getElementById('cookieConsentBanner').style.display = 'block';
        }
    });

    function acceptCookieConsent() {
        localStorage.setItem('cookie_consent_accepted', '1');
        document.getElementById('cookieConsentBanner').style.display = 'none';
    }
</script>
