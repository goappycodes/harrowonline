<?php
/**
 * Create an account (converted from src/pages/register.html).
 * URL: /register/
 */
defined( 'ABSPATH' ) || exit;
hub_set_page( array(
	'description' => 'Create a free Harrow Online Hub account to claim your business, or to list events and classes for your school, club or charity.',
	'hubnav' => 'account',
	'layout' => 'bare',
	'robots' => 'noindex',
	'title' => 'Create an account',
) );
get_header();
?>
<!-- Account pages (redesign, Nov 2026): one card in the site style, with a short "why join" column beside it
     (below it on phones). Same fields, links and copy as before. -->
<div class="authx authx--wide">
  <div class="authx__card">

      <h1 class="authx__title">Create an account</h1>
      <p class="authx__lead">Already have one? <a href="<?php echo esc_url( hub_url( 'sign-in/' ) ); ?>">Sign in</a></p>

      <!-- WP: creates a Hub user. Role = business_owner or organiser from account_type.
           If join_newsletter is ticked: POST to the Beehiiv subscribe endpoint (brief 02 N3) with editions as custom fields,
           signup_source=hub-registration, plus area + account type as tags. -->
      <form class="form" action="<?php echo esc_url( hub_url( 'account/' ) ); ?>" method="post">
        <fieldset class="fieldset">
          <legend class="field__label">I&rsquo;m signing up because&hellip;</legend>
          <div class="choice-grid">
            <label class="choice">
              <input type="radio" name="account_type" value="business" checked data-reveal="#reg-business-note">
              <span>
                <span class="choice__title"><svg class="ho-icon"><use href="#i-store"/></svg>I run a business</span>
                <span class="choice__text">Claim or add your listing free. Offers, events and classes come with the Featured or Local Partner plan.</span>
              </span>
            </label>
            <label class="choice">
              <input type="radio" name="account_type" value="organiser" data-reveal="#reg-organiser">
              <span>
                <span class="choice__title"><svg class="ho-icon"><use href="#i-people"/></svg>I organise events or classes</span>
                <span class="choice__text">For schools, clubs, charities and community groups. Free. Submit events and classes only.</span>
              </span>
            </label>
          </div>
        </fieldset>

        <div id="reg-business-note">
          <p class="help">After you sign up, search for your business and press <strong>Claim this business</strong>, or add it if it isn&rsquo;t listed.</p>
        </div>

        <div class="field">
          <label for="reg-name">Your name</label>
          <input class="input" id="reg-name" type="text" name="name" autocomplete="name" required>
        </div>
        <div class="field">
          <label for="reg-email">Email address</label>
          <input class="input" id="reg-email" type="email" name="email" autocomplete="email" required>
        </div>
        <div class="field">
          <label for="reg-pass">Password</label>
          <input class="input" id="reg-pass" type="password" name="password" autocomplete="new-password" minlength="10" required aria-describedby="reg-pass-help">
          <p class="help" id="reg-pass-help">At least 10 characters.</p>
        </div>

        <div id="reg-organiser" hidden>
          <div class="field">
            <label for="reg-org">Organisation name</label>
            <input class="input" id="reg-org" type="text" name="organisation" autocomplete="organization" aria-describedby="reg-org-help">
            <p class="help" id="reg-org-help">For example, Pinner Park Primary PTA or Harrow Weald Running Club. Shown on your events.</p>
          </div>
        </div>

        <div class="field">
          <label for="reg-area">Your area <span class="opt">(optional)</span></label>
          <select class="select" id="reg-area" name="area" aria-describedby="reg-area-help">
            <option value="">Choose an area</option>
            <option value="belmont">Belmont</option><option value="canons-park">Canons Park</option><option value="edgware">Edgware</option><option value="greenhill">Greenhill</option><option value="harrow-town-centre">Harrow Town Centre</option><option value="harrow-weald">Harrow Weald</option><option value="harrow-on-the-hill">Harrow on the Hill</option><option value="hatch-end">Hatch End</option><option value="headstone">Headstone</option><option value="kenton">Kenton</option><option value="north-harrow">North Harrow</option><option value="pinner">Pinner</option><option value="queensbury">Queensbury</option><option value="rayners-lane">Rayners Lane</option><option value="south-harrow">South Harrow</option><option value="stanmore">Stanmore</option><option value="sudbury-hill">Sudbury Hill and Roxeth</option><option value="wealdstone">Wealdstone</option><option value="west-harrow">West Harrow</option>
          </select>
          <p class="help" id="reg-area-help">Helps us send you news and events near you.</p>
        </div>

        <label class="check">
          <input type="checkbox" name="terms" value="1" required>
          <span>I agree to the <a href="<?php echo esc_url( hub_url( 'terms/' ) ); ?>">terms of use</a> and have read the <a href="<?php echo esc_url( hub_url( 'privacy/' ) ); ?>">privacy notice</a>.</span>
        </label>

        <div class="panel panel--soft">
          <label class="check">
            <input type="checkbox" name="join_newsletter" value="1" checked data-reveal="#reg-editions">
            <span><strong>Join the Harrow Online Newsletter</strong><small>Free local news, what&rsquo;s on and offers. Unsubscribe any time.</small></span>
          </label>
          <div id="reg-editions" class="mt-4">
            <fieldset class="fieldset">
              <legend class="ho-sr">Editions</legend>
              <div class="stack">
                <label class="check"><input type="checkbox" name="editions[]" value="daily" checked> <span>Daily News<small>Every morning</small></span></label>
                <label class="check"><input type="checkbox" name="editions[]" value="weekend"> <span>The Weekend<small>Thursdays: what&rsquo;s on this weekend</small></span></label>
                <label class="check"><input type="checkbox" name="editions[]" value="business"> <span>Harrow Business<small>Mondays: local business news</small></span></label>
              </div>
            </fieldset>
          </div>
          <input type="hidden" name="signup_source" value="hub-registration">
        </div>

        <label class="hp" aria-hidden="true">Leave this field empty <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label>
        <button class="ho-btn ho-btn--primary ho-btn--block" type="submit">Create my account</button>
      </form>
  </div>

  <aside class="authx__perks" aria-label="About your Hub account">
      <p class="authx__eyebrow">Harrow Online Hub</p>
      <h2 class="authx__perks-title">Free to join. Free to claim.</h2>
      <p>One account covers everything you do on the Hub.</p>
      <ul class="ticks authx__ticks">
        <li>Claim your business free, or add one that isn&rsquo;t listed yet</li>
        <li>Post Local Offers, events and weekly classes on the Featured or Local Partner plan</li>
        <li>Reach Harrow Online Newsletter readers with featured spots and sponsor slots</li>
        <li>Schools, clubs and charities add events and classes free</li>
      </ul>
  </aside>
</div>
<?php get_footer();
