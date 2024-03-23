(function () {
    'use strict';

    var myDataService = {
        rate: function (rating) {
            return {
                then: function (callback) {
                    setTimeout(function () {
                        callback(Math.random() * 5);
                    }, 1000);
                },
            };
        },
    };

    /* Basic Rater */
    var starRating1 = raterJs({
        starSize: 24,
        element: document.querySelector("#rater-basic"),
        rateCallback: function rateCallback(rating, done) {
            this.setRating(rating);
            done();
        }
    });

    let ratingRTL = ()=>{

        [document.querySelector("#rater-steps"),document.querySelector("#rater-basic")
        ,document.querySelector("#custom-messages"),document.querySelector("#rater-reset")].map((ele)=>{

            // To remove the child
            ele.firstChild.remove()

            // For RTl
            raterJs({
            starSize: 24,
            reverse:true, //this enable rtl
            element: ele,
            rateCallback: function rateCallback(rating, done) {
                this.setRating(rating);
                done();
            }
            })

        })

        document.querySelector("#stars-busytext").firstChild.remove()

        starRating14 = raterJs({
            starSize: 24,
            reverse:true, //this enable rtl
            isBusyText: "Rating in progress. Please wait...",
            element: document.querySelector("#stars-busytext"),
            rateCallback: function rateCallback(rating, done) {
                starRating14.setRating(rating);
                myDataService.rate().then(function (avgRating) {
                    starRating14.setRating(avgRating);
                    done();
                });
            },
        });

        document.querySelector("#stars-hover").firstChild.remove()

        raterJs({
            starSize: 24,
            reverse:true, //this enable rtl
            element: document.querySelector("#stars-hover"),
            rateCallback: function rateCallback(rating, done) {
                this.setRating(rating);
                done();
            },
            onHover: function (currentIndex, currentRating) {
                document.querySelector('.live-rating').textContent = currentIndex;
            },
            onLeave: function (currentIndex, currentRating) {
                document.querySelector('.live-rating').textContent = currentRating;
            }
        })
    }
    let ratingLTR = ()=>{

        [document.querySelector("#rater-steps"),document.querySelector("#rater-basic")
        ,document.querySelector("#custom-messages"),document.querySelector("#rater-reset")].map((ele)=>{

            // To remove the child
            ele.firstChild.remove()

            // For RTl
            raterJs({
            starSize: 24,
            element: ele,
            rateCallback: function rateCallback(rating, done) {
                this.setRating(rating);
                done();
            }
            })

        })

        document.querySelector("#stars-busytext").firstChild.remove()

        starRating14 = raterJs({
            starSize: 24,
            isBusyText: "Rating in progress. Please wait...",
            element: document.querySelector("#stars-busytext"),
            rateCallback: function rateCallback(rating, done) {
                starRating14.setRating(rating);
                myDataService.rate().then(function (avgRating) {
                    starRating14.setRating(avgRating);
                    done();
                });
            },
        });

        document.querySelector("#stars-hover").firstChild.remove()

        raterJs({
            starSize: 24,
            element: document.querySelector("#stars-hover"),
            rateCallback: function rateCallback(rating, done) {
                this.setRating(rating);
                done();
            },
            onHover: function (currentIndex, currentRating) {
                document.querySelector('.live-rating').textContent = currentIndex;
            },
            onLeave: function (currentIndex, currentRating) {
                document.querySelector('.live-rating').textContent = currentRating;
            }
        })
    }

    document.getElementById("switcher-rtl").addEventListener("click",ratingRTL)
    document.getElementById("switcher-ltr").addEventListener("click",ratingLTR)
    document.getElementById("reset-all").addEventListener("click",ratingLTR)

    if(localStorage.getItem("sashrtl")){
        setTimeout(() => {
            ratingRTL()
        }, 100);
    }


    /* Basic Rater */

    /* 5 star rater with steps */
    var starRating2 = raterJs({
        element: document.querySelector("#rater-steps"),
        rateCallback: function rateCallback(rating, done) {
            this.setRating(rating);
            done();
        },
        starSize: 24,
        step: 0.5
    });
    /* 5 star rater with steps */

    /* Custom messages */
    var starRating3 = raterJs({
        starSize: 24,
        max: 5,
        rating: 4,
        element: document.querySelector("#custom-messages"),
        disableText: "Custom disable text!",
        ratingText: "My custom rating text {rating}",
        showToolTip: true,
        rateCallback: function rateCallback(rating, done) {
            starRating3.setRating(rating);
            starRating3.disable();
            done();
        }
    });
    /* Custom messages */

    /* Unlimited number of stars readOnly */
    var starRating4 = raterJs({
        max: 10,
        starSize: 24,
        readOnly: true,
        rating: 6,
        element: document.querySelector("#stars-unlimited")
    });
    /* Unlimited number of stars readOnly */

    /* 5 Star rater with custom isBusyText and simulated backend */
    var starRating14 = raterJs({
        starSize: 24,
        isBusyText: "Rating in progress. Please wait...",
        element: document.querySelector("#stars-busytext"),
        rateCallback: function rateCallback(rating, done) {
            starRating14.setRating(rating);
            myDataService.rate().then(function (avgRating) {
                starRating14.setRating(avgRating);
                done();
            });
        },
    });
    /* 5 Star rater with custom isBusyText and simulated backend */

    /* On hover event */
    var starRating5 = raterJs({
        starSize: 24,
        element: document.querySelector("#stars-hover"),
        rateCallback: function rateCallback(rating, done) {
            this.setRating(rating);
            done();
        },
        onHover: function (currentIndex, currentRating) {
            document.querySelector('.live-rating').textContent = currentIndex;
        },
        onLeave: function (currentIndex, currentRating) {
            document.querySelector('.live-rating').textContent = currentRating;
        }
    });
    /* On hover event */

    /* Clear/reset rater */
    var starRating6 = raterJs({
        starSize: 24,
        element: document.querySelector("#rater-reset"),
        rateCallback: function rateCallback(rating, done) {
            this.setRating(rating);
            done();
        }
    });
    document.querySelector('#rater-reset-button').addEventListener("click", function () {
        starRating6.clear();
    }, false);
    /* Clear/reset rater */


})();