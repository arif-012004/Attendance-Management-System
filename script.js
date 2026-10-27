// =====================================================
// ATTENDIFY
// WEBSITE JAVASCRIPT
// =====================================================


document.addEventListener("DOMContentLoaded", function () {


    // ================= MOBILE NAVBAR =================

    const navLinks =
        document.querySelectorAll(".nav-link");

    const navbarCollapse =
        document.querySelector(".navbar-collapse");

    const navbarToggler =
        document.querySelector(".navbar-toggler");


    navLinks.forEach(function (link) {

        link.addEventListener("click", function () {

            if (
                navbarCollapse.classList.contains("show")
            ) {

                navbarToggler.click();

            }

        });

    });



    // ================= NAVBAR SCROLL EFFECT =================

    const navbar =
        document.querySelector(".navbar");


    window.addEventListener("scroll", function () {

        if (window.scrollY > 30) {

            navbar.style.boxShadow =
                "0 8px 30px rgba(20,25,50,.07)";

        } else {

            navbar.style.boxShadow = "none";

        }

    });



    // ================= ACTIVE NAVIGATION =================

    const sections =
        document.querySelectorAll("section[id]");


    window.addEventListener("scroll", function () {

        let current = "";


        sections.forEach(function (section) {

            const sectionTop =
                section.offsetTop - 160;

            const sectionHeight =
                section.offsetHeight;


            if (
                window.scrollY >= sectionTop &&
                window.scrollY < sectionTop + sectionHeight
            ) {

                current =
                    section.getAttribute("id");

            }

        });


        navLinks.forEach(function (link) {

            link.classList.remove("active");


            if (
                link.getAttribute("href") ===
                "#" + current
            ) {

                link.classList.add("active");

            }

        });

    });



    // ================= FEATURE HOVER =================

    const featureCards =
        document.querySelectorAll(".feature-card");


    featureCards.forEach(function (card) {

        card.addEventListener(
            "mouseenter",
            function () {

                card.style.transform =
                    "translateY(-6px)";

            }
        );


        card.addEventListener(
            "mouseleave",
            function () {

                card.style.transform =
                    "translateY(0)";

            }
        );

    });


});