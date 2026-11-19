"""
The weekly classes as listed on the Classes and activities page (src/pages/classes.html): one card each, in page
order. Used by tools/gen_classes.py (the prototype page) and tools/export_wp_data.py (WordPress: each class's
"card", and the two Kodak FC entries, which have no class page of their own).
"""
def I(n, c="ho-icon"): return f'<svg class="{c}"><use href="#i-{n}"/></svg>'
import html as _html, re as _re, unicodedata as _ud
def _slug(t):
    t = _ud.normalize("NFKD", _html.unescape(t)).encode("ascii", "ignore").decode()
    return _re.sub(r"-{2,}", "-", _re.sub(r"[^a-zA-Z0-9]+", "-", t.lower()).strip("-"))
# title, type, when, area, age, price, icon, featured, provider, text
# Samples, except the titles in REAL (classes run by real Hub businesses). Keep titles in step with tools/gen_events.py CLASSES.
C = [
 ("Saturday Soccer School: Reception to Year 2", "Children&rsquo;s activities", "Every Saturday, 10am to 11am", "Hatch End", "Ages 4 to 7 (Reception to Year 2)", "&pound;6", "family", True, "Parkfield Youth FC", "Football coaching from DBS-checked, FA-qualified coaches. No booking needed: just turn up."),
 ("Saturday Soccer School: Years 3 to 9", "Children&rsquo;s activities", "Every Saturday, 11am to 12pm", "Hatch End", "Ages 7 to 14 (Years 3 to 9)", "&pound;6", "family", True, "Parkfield Youth FC", "Football coaching from DBS-checked, FA-qualified coaches. No booking needed: just turn up."),
 ("Girls&rsquo; Saturday Soccer School", "Children&rsquo;s activities", "Every Saturday, time to be confirmed", "Hatch End", "Girls (ages to be confirmed)", "Ask the club", "family", True, "Parkfield Youth FC", "A girls-only Soccer School session. Call or email the club for the time before you go."),
 ("Saturday Small Group Tuition", "Children&rsquo;s activities", "Every Saturday, 10am to 2pm", "South Harrow", "Ages 7 to 18 (Years 3 to 13)", "From &pound;8.50 an hour", "edit", True, "GradeSmart Tuition", "Maths, English and Science in groups of six or fewer. Free assessment and 75% off the first month."),
 ("Kids&rsquo; Breakdance", "Dance schools", "Every Monday, 5pm to 6.30pm", "Hatch End", "Ages 8+", "Free trial, then ask for prices", "music", True, "Kidology Dance Co", "Footwork, freezes and power moves at Harrow Arts Centre. Book by half term. Free trial class available."),
 ("Musical Theatre for Kids", "Dance schools", "Every Thursday, 5pm to 6pm", "Hatch End", "Ages 8+", "Free trial, then ask for prices", "music", True, "Kidology Dance Co", "Singing, acting and dance from the musicals. Book by half term. Free trial class available."),
 ("Toddler Dance", "Dance schools", "Every Friday, 5pm to 5.45pm", "Hatch End", "Ages 2 to 4", "Free trial, then ask for prices", "music", True, "Kidology Dance Co", "A first dance class: music, movement and fun. Book by half term. Free trial class available."),
 ("Mini Street Dance", "Dance schools", "Every Friday, 5pm to 6pm", "Hatch End", "Ages 5 to 7", "Free trial, then ask for prices", "music", True, "Kidology Dance Co", "Fun street dance routines to the latest music. Book by half term. Free trial class available."),
 ("Junior Street Dance", "Dance schools", "Every Friday, 5pm to 6pm", "Hatch End", "Ages 8 to 11", "Free trial, then ask for prices", "music", True, "Kidology Dance Co", "Street dance and hip-hop routines. Book by half term. Free trial class available."),
 ("Senior Street Dance and Hip-Hop", "Dance schools", "Every Friday, 6pm to 7pm", "Hatch End", "Ages 12+", "Free trial, then ask for prices", "music", True, "Kidology Dance Co", "Routines for performances and the annual showcase. Book by half term. Free trial class available."),
 ("Kodak FC Youth Football", "Children&rsquo;s activities", "Training and matches, times vary by age group", "Harrow Weald", "Youth teams", "Ask the club", "family", True, "Kodak FC", "Youth teams in the Harrow Youth Football League, with FA-qualified coaches. Call the club to find the right age group."),
 ("Kodak FC Adult Football", "Fitness", "Training and Saturday matches, times vary by team", "Harrow Weald", "Adults", "Ask the club", "people", True, "Kodak FC", "Senior teams in the Middlesex County Football League. New players welcome, including those coming back to football."),
 ("Weekend Bootcamp", "Fitness", "Every Saturday, 9am", "Wealdstone", "Adults", "&pound;10", "dumbbell", True, "Wealdstone Fitness Studio", "Circuits for every fitness level. First session free."),
 ("Evening Yoga Flow", "Fitness", "Every Friday, 6.30pm", "Wealdstone", "Adults", "&pound;10", "dumbbell", True, "Wealdstone Fitness Studio", "An hour of vinyasa yoga to end the week. Mats provided."),
 ("Saturday Kids&rsquo; Coding Club", "Children&rsquo;s activities", "Every Saturday, 10am", "Kenton", "Ages 8 to 12", "&pound;12", "family", False, "Bright Sparks Coding", "Games and animations in Scratch and Python. Laptops provided."),
 ("Story Time", "Children&rsquo;s activities", "Every Saturday, 10.30am", "Hatch End", "Ages 3 to 6", "Free", "cup", False, "Hatch End Bookshop &amp; Caf&eacute;", "Picture books, songs and a craft, with a babyccino for grown-ups."),
 ("Junior Ballet", "Dance schools", "Every Saturday, 9.30am", "Pinner", "Ages 4 to 7", "&pound;9", "music", False, "Pinner Village Hall", "Playful ballet for little dancers, working towards a summer show."),
 ("Street Dance for Teens", "Dance schools", "Every Friday, 6pm", "Rayners Lane", "Ages 12 to 16", "&pound;7", "music", False, "Rayners Lane Youth Hall", "Hip hop and commercial routines. No experience needed."),
 ("Beginners&rsquo; Pottery", "Workshops", "Tuesdays, 7pm (6 weeks)", "Hatch End", "Adults", "&pound;150 course", "edit", False, "Harrow Arts Centre", "Learn to throw and glaze on the wheel. Clay and firing included."),
 ("Toddler Rhyme Time", "Children&rsquo;s activities", "Every Thursday, 10.30am", "Stanmore", "Under 4s", "Free", "family", False, "Stanmore Library", "Songs, rhymes and stories for little ones. Just turn up."),
 ("Lunchtime Spin", "Fitness", "Every Wednesday, 12.30pm", "Wealdstone", "Adults", "&pound;8", "dumbbell", False, "Wealdstone Fitness Studio", "45 minutes of high-energy spin, back at your desk by 1.30pm."),
 ("Chair Yoga for Over-60s", "Fitness", "Every Monday, 11am", "Headstone", "Ages 60+", "&pound;3", "heart", False, "St George&rsquo;s Hall", "Gentle seated yoga to help with balance and movement."),
 ("Conversational Spanish", "Other regular classes", "Every Thursday, 7pm", "North Harrow", "Adults", "&pound;90 for 10 weeks", "people", False, "North Harrow Community Library", "A relaxed evening class for improvers. Join any week."),
]
# Real classes: (image, alt, provider page, card link or None for the class's own page, "logo" to show the image whole)
_PF = ("/assets/img/business/parkfield-youth-fc-team.jpg", "Parkfield Youth FC players in a team huddle", "/business/parkfield-youth-fc-hatch-end.html", None, "")
_KDC = ("/assets/img/business/kidology-dance-co-dancers.jpg", "Young Kidology dancers in KDC T-shirts", "/business/kidology-dance-co-hatch-end.html", None, "")
_KODAK = "/business/kodak-fc-harrow-weald.html"
REAL = {
    **{t: _PF for t in ("Saturday Soccer School: Reception to Year 2", "Saturday Soccer School: Years 3 to 9", "Girls&rsquo; Saturday Soccer School")},
    "Saturday Small Group Tuition": ("/assets/img/business/gradesmart-tuition-logo.jpg", "GradeSmart Tuition logo", "/business/gradesmart-tuition-south-harrow.html", None, "logo"),
    **{t: _KDC for t in ("Kids&rsquo; Breakdance", "Musical Theatre for Kids", "Toddler Dance", "Mini Street Dance", "Junior Street Dance", "Senior Street Dance and Hip-Hop")},
    # Kodak FC's teams train at times that vary, so they have no weekly class page: the card links to the club
    "Kodak FC Youth Football": ("/assets/img/business/kodak-fc-badge.jpg", "Kodak FC badge", _KODAK, _KODAK, "logo"),
    "Kodak FC Adult Football": ("/assets/img/business/kodak-fc-badge.jpg", "Kodak FC badge", _KODAK, _KODAK, "logo"),
}
TYPE_SLUG = {"Children&rsquo;s activities": "childrens-activities", "Fitness": "fitness", "Dance schools": "dance-schools",
             "Workshops": "workshops", "Other regular classes": "other"}
DAY_SLUG = {d.lower(): d[:3].lower() for d in ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]}


def _day(when):
    w = when.lower()
    return next((v for k, v in DAY_SLUG.items() if k in w), "")


def _ages(age):
    """Age buckets a class suits (matches the Age filter values)."""
    a = age.lower()
    if "adult" in a:
        return "adults"
    if "60" in a:
        return "60-plus adults"
    nums = [int(n) for n in __import__("re").findall(r"\d+", a)]
    if "under" in a and nums:
        lo, hi = 0, nums[0] - 1
    elif "+" in a and nums:
        lo, hi = nums[0], 120
    elif len(nums) >= 2:
        lo, hi = nums[0], nums[1]
    elif nums:
        lo, hi = nums[0], nums[0]
    elif "girl" in a or "boy" in a or "youth" in a:
        return "under-5 5-11 12-17"   # children's session, exact ages not published yet
    else:
        return "under-5 5-11 12-17 adults 60-plus"
    out = []
    for name, a0, a1 in (("under-5", 0, 4), ("5-11", 5, 11), ("12-17", 12, 17), ("adults", 18, 120)):
        if lo <= a1 and hi >= a0:
            out.append(name)
    return " ".join(out)


