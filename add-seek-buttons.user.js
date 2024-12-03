// ==UserScript==
// @name         Add Seek Divs to Video.js Player
// @namespace    http://tampermonkey.net/
// @version      1.3
// @description  Add seek forward/backward div controls to Video.js player on example.com
// @author       YourName
// @match        https://desipin.com/*
// @grant        none
// ==/UserScript==

(function() {
    'use strict';

    /**
     * Wait until the Video.js library and the video player are fully initialized
     */
    function waitForVideoJs() {
        const interval = setInterval(() => {
            const videoElement = document.getElementById('censor-player_html5_api');

            // Check if the Video.js player exists and has initialized
            if (videoElement && typeof videojs !== 'undefined') {
                const player = videojs(videoElement.id);
                if (player.readyState() > 0) { // Ensure the player is ready
                    clearInterval(interval);
                    addSeekDivs(player);
                }
            }
        }, 500);
    }

    /**
     * Add seek divs to the Video.js control bar
     * @param {Object} player - The Video.js player instance
     */
    function addSeekDivs(player) {
        // Create the backward seek control
        const backwardDiv = document.createElement('div');
        backwardDiv.className = 'vjs-seek-control vjs-seek-backward';
        backwardDiv.innerHTML = `
            <span class="vjs-control-text" role="presentation">Seek Backward</span>
            <span aria-hidden="true">-10s</span>
        `;
        backwardDiv.addEventListener('click', () => {
            player.currentTime(player.currentTime() - 10);
        });

        // Create the forward seek control
        const forwardDiv = document.createElement('div');
        forwardDiv.className = 'vjs-seek-control vjs-seek-forward';
        forwardDiv.innerHTML = `
            <span class="vjs-control-text" role="presentation">Seek Forward</span>
            <span aria-hidden="true">+10s</span>
        `;
        forwardDiv.addEventListener('click', () => {
            player.currentTime(player.currentTime() + 10);
        });

        // Append controls to the control bar
        const controlBar = player.getChild('controlBar').el();
        controlBar.appendChild(backwardDiv);
        controlBar.appendChild(forwardDiv);

        console.log('Seek divs added to Video.js player');
    }

    /**
     * Add custom styles for seek divs
     */
    function addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .vjs-seek-control {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 14px;
                margin: 0 5px;
                padding: 5px 10px;
                cursor: pointer;
                min-width: 40px; /* Ensures consistent size */
                height: 100%; /* Match the height of the control bar */
            }

            .vjs-seek-control:hover {
                color: #ff5722;
            }
        `;
        document.head.appendChild(style);
    }

    // Start the script
    addStyles();
    waitForVideoJs();
})();
