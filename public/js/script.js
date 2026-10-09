

/* REGISTRATION PAGE */









/* Change the selected account type.*/
function selectAccountType(type, button) {
    if (!["student", "company"].includes(type)) {
        return;
    }

    document.getElementById("accountType").value = type;

    document.querySelectorAll(".account-type-btn").forEach(function (item) {
        item.classList.remove("active");
    });

    button.classList.add("active");

    const label = document.querySelector('label[for="fullName"]');
    const input = document.getElementById("fullName");
    const message = document.getElementById("registrationMessage");

    label.innerHTML = type === "company"
        ? 'Company Name <span>*</span>'
        : 'Full Name <span>*</span>';

    input.placeholder = type === "company"
        ? "Enter your company name"
        : "Enter your full name";

    message.className = "registration-message";
    message.textContent = "";
}










/* Show / hide password.*/
function togglePassword(inputId, button) {

    const input =
        document.getElementById(inputId);

    const icon =
        button.querySelector("i");


    if (input.type === "password") {

        input.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");

    } else {

        input.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");

    }
}


/* Registration form validation.*/
function registerUser(event) {

    event.preventDefault();


    const password =
        document.getElementById("password").value;

    const confirmPassword =
        document.getElementById("confirmPassword").value;

    const message =
        document.getElementById("registrationMessage");


    // Check password
    if (password !== confirmPassword) {

        message.textContent =
            "Passwords do not match.";

        message.className =
            "registration-message error";

        return;
    }


    // Check password length
    if (password.length < 8) {

        message.textContent =
            "Password must contain at least 8 characters.";

        message.className =
            "registration-message error";

        return;
    }


    // Get selected account type
    const accountType =
        document.getElementById("accountType").value;


    // Temporary success message
    message.textContent =
        "Account information is valid. Selected account type: "
        + accountType
        + ".";

    message.className =
        "registration-message success";
}


/*  LOGIN PAGE */


/* Select login account type.*/
function selectLoginType(type, button) {

    // Change hidden login type
    document.getElementById("loginType").value = type;


    // Get all account type buttons
    const buttons =
        document.querySelectorAll(".account-type-btn");


    // Remove active state
    buttons.forEach(function (item) {

        item.classList.remove("active");

    });


    // Activate selected button
    button.classList.add("active");
}


/*
    Login form validation.
*/
function loginUser(event) {

    event.preventDefault();


    const email =
        document.getElementById("loginEmail").value.trim();

    const password =
        document.getElementById("loginPassword").value;

    const loginType =
        document.getElementById("loginType").value;

    const message =
        document.getElementById("loginMessage");


    // Check email
    if (email === "") {

        message.textContent =
            "Please enter your email address.";

        message.className =
            "registration-message error";

        return;
    }


    // Check password
    if (password === "") {

        message.textContent =
            "Please enter your password.";

        message.className =
            "registration-message error";

        return;
    }


    /*
        This is only frontend testing.

        Later this section will send the
        login information to PHP.
    */

    message.textContent =
        "Login information is valid. "
        + "Account type: "
        + loginType
        + ".";

    message.className =
        "registration-message success";
}


/* STUDENT DASHBOARD */
/* Save / unsave an internship. */
function toggleSave(button) {

    const icon = button.querySelector("i");


    if (button.classList.contains("saved")) {

        button.classList.remove("saved");

        icon.classList.remove("bi-bookmark-fill");

        icon.classList.add("bi-bookmark");

    } else {

        button.classList.add("saved");

        icon.classList.remove("bi-bookmark");

        icon.classList.add("bi-bookmark-fill");

    }
}





/*  CV FILE */

function showSelectedCV(input) {

    if (input.files && input.files.length > 0) {

        const fileName = input.files[0].name;

        const cvTitle = document.querySelector(".cv-information h5");
        const cvDescription = document.querySelector(".cv-information p");

        cvTitle.textContent = fileName;
        cvDescription.textContent = "CV selected and ready to upload.";

    }

}


/* OPPORTUNITIES PAGE */

/* SAVE INTERNSHIP */

function toggleOpportunitySave(button) {

    const icon = button.querySelector("i");

    if (button.classList.contains("saved")) {

        button.classList.remove("saved");

        icon.classList.remove("bi-bookmark-fill");
        icon.classList.add("bi-bookmark");

    } else {

        button.classList.add("saved");

        icon.classList.remove("bi-bookmark");
        icon.classList.add("bi-bookmark-fill");

    }

}


/* FILTER INTERNSHIPS */

function filterInternships() {

    const searchValue =
        document.getElementById("opportunitySearch").value
        .toLowerCase()
        .trim();

    const location =
        document.getElementById("locationFilter").value;

    const duration =
        document.getElementById("durationFilter").value;

    const field =
        document.getElementById("fieldFilter").value;

    const minimumMatch =
        parseInt(document.getElementById("matchFilter").value);


    /* Selected internship types */

    const selectedTypes =
        Array.from(
            document.querySelectorAll(".type-filter:checked")
        ).map(function (checkbox) {
            return checkbox.value;
        });


    const cards =
        document.querySelectorAll(".opportunity-card");

    let visibleCount = 0;


    cards.forEach(function (card) {

        const title =
            card.dataset.title.toLowerCase();

        const company =
            card.dataset.company.toLowerCase();

        const cardLocation =
            card.dataset.location;

        const cardType =
            card.dataset.type;

        const cardDuration =
            card.dataset.duration;

        const cardField =
            card.dataset.field;

        const cardMatch =
            parseInt(card.dataset.match);


        /* Search */

        const matchesSearch =
            searchValue === "" ||
            title.includes(searchValue) ||
            company.includes(searchValue);


        /* Location */

        const matchesLocation =
            location === "" ||
            cardLocation === location;


        /* Duration */

        const matchesDuration =
            duration === "" ||
            cardDuration === duration;


        /* Field */

        const matchesField =
            field === "" ||
            cardField === field;


        /* Internship Type */

        const matchesType =
            selectedTypes.length === 0 ||
            selectedTypes.includes(cardType);


        /* Match */

        const matchesScore =
            cardMatch >= minimumMatch;


        /* Final result */

        const showCard =
            matchesSearch &&
            matchesLocation &&
            matchesDuration &&
            matchesField &&
            matchesType &&
            matchesScore;


        if (showCard) {

            card.style.display = "block";
            visibleCount++;

        } else {

            card.style.display = "none";

        }

    });


    /* Update result count */

    document.getElementById("resultCount").textContent =
        visibleCount;


    /* Show no results */

    const noResults =
        document.getElementById("noInternships");

    if (visibleCount === 0) {

        noResults.style.display = "block";

    } else {

        noResults.style.display = "none";

    }

}


/* CLEAR FILTERS */

function clearFilters() {

    document.getElementById("opportunitySearch").value = "";

    document.getElementById("locationFilter").value = "";

    document.getElementById("durationFilter").value = "";

    document.getElementById("fieldFilter").value = "";

    document.getElementById("matchFilter").value = "0";


    document
        .querySelectorAll(".type-filter")
        .forEach(function (checkbox) {

            checkbox.checked = false;

        });


    filterInternships();

}


/*  SORT INTERNSHIPS */

function sortInternships() {

    const sortValue =
        document.getElementById("sortFilter").value;

    const list =
        document.getElementById("internshipList");

    const cards =
        Array.from(
            list.querySelectorAll(".opportunity-card")
        );


    cards.sort(function (a, b) {

        if (sortValue === "match") {

            return parseInt(b.dataset.match)
                - parseInt(a.dataset.match);

        }


        if (sortValue === "title") {

            return a.dataset.title
                .localeCompare(b.dataset.title);

        }


        if (sortValue === "latest") {

            return 0;

        }

    });


    cards.forEach(function (card) {

        list.appendChild(card);

    });


    filterInternships();

}


/* VIEW INTERNSHIP */

function viewInternship(title) {

    alert(
        "Internship details for:\n\n" +
        title +
        "\n\nThe full internship details page will be connected later."
    );

}


/* INITIAL FILTER */

document.addEventListener("DOMContentLoaded", function () {

    if (document.getElementById("internshipList")) {

        filterInternships();

    }

});


/* INTERNSHIP DETAILS PAGE */
/* SAVE INTERNSHIP */

function toggleDetailsSave(button) {

    const icon = button.querySelector("i");

    if (button.classList.contains("saved")) {

        button.classList.remove("saved");

        icon.classList.remove("bi-bookmark-fill");
        icon.classList.add("bi-bookmark");

        button.innerHTML =
            '<i class="bi bi-bookmark"></i> Save Internship';

    } else {

        button.classList.add("saved");

        button.innerHTML =
            '<i class="bi bi-bookmark-fill"></i> Saved';

    }

}


/* APPLY FOR INTERNSHIP */

function applyForInternship() {

    const confirmed = confirm(
        "Are you ready to apply for this internship?"
    );

    if (confirmed) {

        alert(
            "Your application has been started.\n\n" +
            "The application submission page will be connected later."
        );

    }

}


/* APPLICATION PAGE */

function submitApplication() {

    const coverLetter = document.getElementById("coverLetter").value.trim();
    const confirmation = document.getElementById("confirmInformation");

    if (coverLetter === "") {
        alert("Please write a cover letter before submitting.");
        document.getElementById("coverLetter").focus();
        return;
    }

    if (coverLetter.length < 50) {
        alert("Your cover letter should contain at least 50 characters.");
        document.getElementById("coverLetter").focus();
        return;
    }

    if (!confirmation.checked) {
        alert("Please confirm that your information is accurate.");
        return;
    }

    const confirmed = confirm(
        "Are you sure you want to submit your application?"
    );

    if (confirmed) {

        alert(
            "Your application has been submitted successfully!\n\n" +
            "Application Status: Pending"
        );

        window.location.href = "student-dashboard.php";
    }
}


/*  COVER LETTER CHARACTER COUNT */

document.addEventListener("DOMContentLoaded", function () {

    const coverLetter = document.getElementById("coverLetter");
    const characterCount = document.getElementById("characterCount");

    if (coverLetter && characterCount) {

        coverLetter.addEventListener("input", function () {

            if (this.value.length > 1000) {
                this.value = this.value.substring(0, 1000);
            }

            characterCount.textContent = this.value.length;

        });

    }

});

/* MY APPLICATIONS PAGE */

function filterApplications(status, button) {

    const cards = document.querySelectorAll(".my-application-card");

    const noApplications =
        document.getElementById("noApplications");

    let visibleCount = 0;


    /* Change active filter button */
    document.querySelectorAll(".application-filter-button")
        .forEach(function (filterButton) {

            filterButton.classList.remove("active");

        });


    button.classList.add("active");


    /* Filter application cards */
    cards.forEach(function (card) {

        const cardStatus = card.dataset.status;

        if (status === "all" || cardStatus === status) {

            card.style.display = "flex";
            visibleCount++;

        } else {

            card.style.display = "none";

        }

    });


    /* Show / hide no-results message */
    if (visibleCount === 0) {

        noApplications.style.display = "block";

    } else {

        noApplications.style.display = "none";

    }

}


/* =========================================
   SORT APPLICATIONS
========================================= */

function sortApplications() {

    const sortValue =
        document.getElementById("applicationSort").value;

    const list =
        document.getElementById("applicationList");

    const cards =
        Array.from(list.querySelectorAll(".my-application-card"));


    cards.sort(function (a, b) {

        if (sortValue === "latest") {

            return new Date(b.dataset.date) -
                   new Date(a.dataset.date);

        }


        if (sortValue === "oldest") {

            return new Date(a.dataset.date) -
                   new Date(b.dataset.date);

        }


        if (sortValue === "match") {

            return parseInt(b.dataset.match) -
                   parseInt(a.dataset.match);

        }


        if (sortValue === "title") {

            return a.dataset.title.localeCompare(
                b.dataset.title
            );

        }

    });


    cards.forEach(function (card) {

        list.appendChild(card);

    });

}


/* VIEW APPLICATION STATUS */

function viewApplicationStatus(title) {

    alert(
        "Application Status\n\n" +
        title +
        "\n\n" +
        "Your application status details will be connected " +
        "to the database later."
    );

}

/* SAVED INTERNSHIPS PAGE */

function searchSavedInternships() {

    const searchValue =
        document.getElementById("savedSearch")
        .value
        .toLowerCase()
        .trim();

    const cards =
        document.querySelectorAll(".saved-internship-card");

    let visibleCount = 0;


    cards.forEach(function (card) {

        const title =
            card.dataset.title.toLowerCase();

        const company =
            card.dataset.company.toLowerCase();


        const matchesSearch =
            searchValue === "" ||
            title.includes(searchValue) ||
            company.includes(searchValue);


        if (matchesSearch) {

            card.style.display = "flex";
            visibleCount++;

        } else {

            card.style.display = "none";

        }

    });


    document.getElementById("savedCount").textContent =
        visibleCount;


    const noSaved =
        document.getElementById("noSavedInternships");


    if (visibleCount === 0) {

        noSaved.style.display = "block";

    } else {

        noSaved.style.display = "none";

    }

}


/* SORT SAVED INTERNSHIPS */

function sortSavedInternships() {

    const sortValue =
        document.getElementById("savedSort").value;

    const list =
        document.getElementById("savedInternshipList");

    const cards =
        Array.from(
            list.querySelectorAll(".saved-internship-card")
        );


    cards.sort(function (a, b) {

        if (sortValue === "latest") {

            return new Date(b.dataset.date) -
                   new Date(a.dataset.date);

        }


        if (sortValue === "match") {

            return parseInt(b.dataset.match) -
                   parseInt(a.dataset.match);

        }


        if (sortValue === "title") {

            return a.dataset.title.localeCompare(
                b.dataset.title
            );

        }

    });


    cards.forEach(function (card) {

        list.appendChild(card);

    });

}


/* REMOVE SAVED INTERNSHIP */

function removeSavedInternship(button) {

    const card =
        button.closest(".saved-internship-card");

    const title =
        card.dataset.title;


    const confirmed = confirm(
        "Remove \"" + title +
        "\" from your saved internships?"
    );


    if (confirmed) {

        card.remove();


        const remainingCards =
            document.querySelectorAll(
                ".saved-internship-card"
            );


        document.getElementById("savedCount")
            .textContent = remainingCards.length;


        if (remainingCards.length === 0) {

            document.getElementById(
                "noSavedInternships"
            ).style.display = "block";

        }

    }

}

/* NOTIFICATIONS PAGE */

function filterNotifications(type, button) {

    const cards =
        document.querySelectorAll(".notification-card");

    const noNotifications =
        document.getElementById("noNotifications");

    let visibleCount = 0;


    // Remove active class
    document.querySelectorAll(".notification-tab")
        .forEach(function (tab) {

            tab.classList.remove("active");

        });


    // Add active class to selected tab
    button.classList.add("active");


    cards.forEach(function (card) {

        const cardType = card.dataset.type;
        const isUnread = card.classList.contains("unread");

        let showCard = false;


        if (type === "all") {

            showCard = true;

        } else if (type === "unread") {

            showCard = isUnread;

        } else if (type === cardType) {

            showCard = true;

        }


        if (showCard) {

            card.style.display = "flex";
            visibleCount++;

        } else {

            card.style.display = "none";

        }

    });


    // Show or hide empty message
    if (visibleCount === 0) {

        noNotifications.style.display = "block";

    } else {

        noNotifications.style.display = "none";

    }

}


/* MARK ONE NOTIFICATION AS READ */

function markAsRead(button) {

    const card =
        button.closest(".notification-card");

    if (!card) {
        return;
    }


    card.classList.remove("unread");


    // Remove unread blue dot
    const dot =
        card.querySelector(".notification-status-dot");

    if (dot) {
        dot.remove();
    }


    // Update unread count
    updateUnreadCount();

}


/* MARK ALL AS READ */

function markAllAsRead() {

    const cards =
        document.querySelectorAll(".notification-card");


    cards.forEach(function (card) {

        card.classList.remove("unread");


        const dot =
            card.querySelector(".notification-status-dot");

        if (dot) {
            dot.remove();
        }

    });


    updateUnreadCount();

}


/* UPDATE UNREAD COUNT */

function updateUnreadCount() {

    const unreadCards =
        document.querySelectorAll(
            ".notification-card.unread"
        );

    const unreadCount =
        document.getElementById("unreadCount");


    if (unreadCount) {

        unreadCount.textContent =
            unreadCards.length;

    }

}

/* COMPANY DASHBOARD */

function showPostInternshipMessage() {

    alert(
        "Post Internship\n\n" +
        "The internship creation form will be connected " +
        "to the PHP/MySQL backend later."
    );

}





function scrollToApplications() {

    const applications =
        document.getElementById("applications");

    if (applications) {

        applications.scrollIntoView({
            behavior: "smooth"
        });

    }

}




function showAllApplicantsMessage() {

    alert(
        "Applications\n\n" +
        "The full applicant management section will be " +
        "connected to the backend later."
    );

}


function showApplicantMessage(name) {

    alert(
        "Applicant Review\n\n" +
        "Reviewing the profile and CV of " +
        name +
        " will be connected to the backend later."
    );

}

/* ADMIN DASHBOARD */

function showAdminSettings() {
    alert(
        "System Settings\n\n" +
        "System configuration and administrator settings " +
        "will be connected to the backend later."
    );
}


function showManagementMessage(title) {
    alert(
        title +
        "\n\n" +
        "This management section will be connected " +
        "to the PHP/MySQL backend later."
    );
}




/* Prevent accidental duplicate form submissions */

document.addEventListener("submit", function (event) {
    const form = event.target;

    if (
    !(form instanceof HTMLFormElement) ||
    event.defaultPrevented
) {
    return;
}

    if (form.dataset.allowRepeat === "true") {
        return;
    }

    if (form.dataset.submitting === "true") {
        event.preventDefault();
        return;
    }

    const submitButton =
        event.submitter ||
        form.querySelector(
            'button[type="submit"], input[type="submit"]'
        );

    if (!submitButton) {
        return;
    }

    form.dataset.submitting = "true";
    submitButton.disabled = true;

    if (submitButton.tagName === "BUTTON") {
        submitButton.dataset.originalText =
            submitButton.innerHTML;

        submitButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" ' +
            'aria-hidden="true"></span>Processing...';
    }
});



/* Restore forms when returning through browser history. */
window.addEventListener("pageshow", function () {
    document.querySelectorAll(
        'form[data-submitting="true"]'
    ).forEach(function (form) {
        delete form.dataset.submitting;

        form.querySelectorAll(
            'button[type="submit"], input[type="submit"]'
        ).forEach(function (button) {
            if (
                button.tagName === "BUTTON" &&
                button.dataset.originalText !== undefined
            ) {
                button.innerHTML = button.dataset.originalText;
                delete button.dataset.originalText;
                button.disabled = false;
            }
        });
    });
});





(function () {
    function initializeOpportunityFilterToggle() {
        const panel = document.getElementById("opportunityFiltersPanel");
        const button = document.getElementById("opportunityFilterToggle");

        if (!panel || !button) {
            return;
        }

        const label = button.querySelector("[data-filter-toggle-label]");

        if (!label) {
            return;
        }

        panel.addEventListener("shown.bs.collapse", function () {
            label.textContent = "Hide filters";
        });

        panel.addEventListener("hidden.bs.collapse", function () {
            label.textContent = "Show filters";
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initializeOpportunityFilterToggle
        );
    } else {
        initializeOpportunityFilterToggle();
    }
})();




(function () {
    "use strict";

    function showCompanyLogoFallback(image) {
        if (!(image instanceof HTMLImageElement)) {
            return;
        }

        if (!image.matches("[data-company-logo-image]")) {
            return;
        }

        const container = image.closest("[data-company-logo]");

        if (!container) {
            return;
        }

        const fallback = container.querySelector(
            ".im-company-logo-fallback"
        );

        if (!fallback) {
            return;
        }

        image.hidden = true;
        fallback.hidden = false;
    }

    // Image error events require capture here.
    document.addEventListener("error", function (event) {
        showCompanyLogoFallback(event.target);
    }, true);

    function checkCompanyLogos() {
        document.querySelectorAll(
            "img[data-company-logo-image]"
        ).forEach(function (image) {
            // Also handle images that failed before this script loaded.
            if (image.complete && image.naturalWidth === 0) {
                showCompanyLogoFallback(image);
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            checkCompanyLogos,
            { once: true }
        );
    } else {
        checkCompanyLogos();
    }
})();