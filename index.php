<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoundrel</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            display: grid;
            height: 100%;
            font-size: clamp(12px, 10vw, 100px);
        }

        .just {
            justify-self: center;
        }

        .align {
            align-self: center;
        }

        .tight {
            width: min-content;
            height: min-content;
        }

        #deck_n_board {
            display: grid;
            grid-template-rows: min-content min-content min-content min-content;
            width: min-content;
            gap: clamp(0px, 2vw, 30px);
            align-self: center;
            justify-self: center;
            border: 2px solid grey;
            padding: 0.5rem 0.5rem;
        }

        #the_deck_card_holder {
            display: grid;
            grid-template-columns: calc(50% - 200px) auto;
        }

        #the_deck_card {
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        #four_board {
            display: grid;
            grid-template-columns: 1fr min-content min-content min-content;
            gap: clamp(0px, 2vw, 30px);
        }

        #weapon_board {
            display: grid;
            grid-template-columns: max-content max-content max-content max-content;
            grid-auto-flow: column;
            width: 50vw;
            overflow-x: auto;
            align-content: center;
        }

        #weapon_board[bare_handed=true] {
            opacity: 0.5;
        }

        #health {
            display: grid;
            border-radius: 50%;
            border: 1px solid red;
            text-align: center;
            padding: 10px;
            width: min-content;
            aspect-ratio: 1 / 1;
            box-sizing: border-box;
        }

        .card,
        #health {
            /* font-size: 6rem; */
            user-select: none;
        }
    </style>
</head>

<template id="card_template">
    <div class="card just align tight" onclick="cardClicked(this)">🂠</div>
</template>
<template id="active_weapon_card_template">
    <div class="card just align tight" card_type="active_weapon" onclick="cardClicked(this)">🂠</div>
</template>
<template id="one_of_four_card_template_used">
    <div class="card just align tight" card_type="one_of_four" used_this_turn="false" onclick="cardClicked(this)">🂠</div>
</template>
<template id="one_of_four_card_template_not_used">
    <div class="card just align tight" card_type="one_of_four" used_this_turn="true" onclick="cardClicked(this)">🂠</div>
</template>

<body>
    <div id="deck_n_board">
        <div id="the_deck_card_holder">
            <div id="health">20</div>
            <!-- Added onclick event here -->
            <div id="the_deck_card" class="card just align" onclick="deckClicked()">🂠</div>
        </div>
        <div id="four_board" class="just"></div>
        <div id="weapon_board" class="just"></div>
    </div>
</body>

</html>

<script>
    /**
     * @typedef {Object} Weapon
     * @property {number} card_power
     * @property {number} lastKill
     */

    /**
     * @typedef {Record<string, Record<string, string>>} CardDatabase
     */

    // Extend Window interface for local globals using JSDoc
    /**
     * @ Our Global Extension declarations
     * @typedef {Window & {
     * hasRunAwayLastTurn: boolean,
     * inTheRoom: boolean,
     * hasHealedThisTurn: boolean,
     * equippedWeapon: Weapon | null,
     * disabledWeapon: Weapon | null,
     * cardClicked: (clickedCard: HTMLElement) => void,
     * deckClicked: () => void
     * }} CustomWindow
     */

    (async () => {
        /** @type {Response} */
        const response = await fetch("./cards.json");
        /** @type {CardDatabase} */
        const all_cards_chars = await response.json();

        // Cached DOM Elements with explicit structural types
        /** @type {HTMLElement} */
        const health = /** @type {HTMLElement} */ (document.getElementById("health"));
        /** @type {HTMLElement} */
        const four_board = /** @type {HTMLElement} */ (document.getElementById("four_board"));
        /** @type {HTMLElement} */
        const weapon_board = /** @type {HTMLElement} */ (document.getElementById("weapon_board"));
        /** @type {HTMLElement} */
        const the_deck_card = /** @type {HTMLElement} */ (document.getElementById("the_deck_card"));

        /** @type {CustomWindow} */
        const game_state = /** @type {any} */ (window);

        game_state.hasRunAwayLastTurn = false;
        game_state.inTheRoom = false;
        game_state.hasHealedThisTurn = false;
        game_state.equippedWeapon = null;
        game_state.disabledWeapon = null;

        /**
         * @param {HTMLElement} clickedCard 
         */
        game_state.cardClicked = function (clickedCard) {
            const clickedCardType = clickedCard.getAttribute("card_type");
            console.log("clickedCardType", clickedCardType);
            switch (clickedCardType) {
                case "one_of_four":
                    clickedOneOfFour(clickedCard);
                    break;
                case "active_weapon":
                    clickedActiveWeapon();
                    break;
                default:
                    break;
            }
        }

        game_state.deckClicked = function () {
            if (game_state.hasRunAwayLastTurn || game_state.inTheRoom) {
                alert("You cannot run away from two rooms in sequence!");
                return;
            }

            /** @type {NodeListOf<HTMLElement>} */
            const unusedCards = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=false]');
            if (unusedCards.length === 0) return;

            // Return unchosen face-up cards back to the deck pool
            const fullDeckMap = shuffledDeck(all_cards_chars);
            unusedCards.forEach(card => {
                const char = card.innerText;
                if (char && char !== cardChar("blank", "1")) {
                    deck[char] = fullDeckMap[char];
                }
            });

            // Set runaway restriction state and update UI
            game_state.hasRunAwayLastTurn = true;
            the_deck_card.style.opacity = "0.5";
            the_deck_card.style.pointerEvents = "none";

            // Clear board and deal 4 new cards from the deck
            four_board.innerHTML = "";
            for (let i = 0; i < 4; i++) {
                const random_card_char = randomCardChar();
                if (random_card_char) {
                    delete deck[random_card_char];
                    four_board.appendChild(cardElement(random_card_char, "one_of_four_card_template_used"));
                }
            }
            game_state.hasHealedThisTurn = false;
        };

        /**
         * @param {any[]} array 
         */
        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        /**
         * @param {CardDatabase} all_cards_chars 
         * @returns {Record<string, string>}
         */
        function shuffledDeck(all_cards_chars) {
            /** @type {Record<string, string>} */
            let deck = {};
            for (const card_symbol in all_cards_chars) {
                if (!Object.hasOwn(all_cards_chars, card_symbol)) continue;
                const symbol_cards = all_cards_chars[card_symbol];

                for (const card_power in symbol_cards) {
                    if (!Object.hasOwn(symbol_cards, card_power)) continue;

                    const card_char = symbol_cards[card_power];
                    deck[card_char] = card_power;
                }
            }

            // Object-key array transformations for shuffling structures inside record arrays
            const keys = Object.keys(deck);
            shuffleArray(keys);
            
            /** @type {Record<string, string>} */
            let shuffled = {};
            keys.forEach(key => {
                shuffled[key] = deck[key];
            });
            return shuffled;
        }

        /** @type {Record<string, string>} */
        let deck = shuffledDeck(all_cards_chars);

        /**
         * @param {string} card_char 
         * @param {string} template_id 
         * @returns {DocumentFragment}
         */
        function cardElement(card_char, template_id) {
            /** @type {HTMLTemplateElement} */
            const template = /** @type {HTMLTemplateElement} */ (document.querySelector("#" + template_id));
            const fragment = /** @type {DocumentFragment} */ (template.content.cloneNode(true));
            /** @type {HTMLElement} */
            const cardDiv = /** @type {HTMLElement} */ (fragment.querySelector('.card'));
            cardDiv.innerText = card_char;
            return fragment;
        }

        /**
         * @param {string} card_symbol 
         * @param {string} card_power 
         * @returns {string}
         * @throws {Error}
         */
        function cardChar(card_symbol, card_power) {
            if (!all_cards_chars[card_symbol] || !all_cards_chars[card_symbol][card_power]) {
                throw new Error("card_symbol or card_power not passed");
            }
            return all_cards_chars[card_symbol][card_power];
        }

        /**
         * @returns {string}
         */
        function randomCardChar() {
            const deck_keys = Object.keys(deck);
            const random_card_index = Math.floor(Math.random() * (deck_keys.length - 1));
            return deck_keys[random_card_index];
        }

        function refreshFourBoard() {
            four_board.innerHTML = "";
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
        }

        function newRoom() {
            game_state.hasRunAwayLastTurn = false;
            the_deck_card.style.opacity = "1";
            the_deck_card.style.pointerEvents = "auto";
            game_state.inTheRoom = false;
        }

        /**
         * @param {HTMLElement} pickedCard 
         * @returns {string | void}
         */
        function cardPicked(pickedCard) {
            pickedCard.setAttribute("used_this_turn", "true");
            let card_power = parseInt(shuffledDeck(all_cards_chars)[pickedCard.innerText]);
            /** @type {string | null} */
            let card_symbol = null;
            
            for (const symbol in all_cards_chars) {
                if (!Object.hasOwn(all_cards_chars, symbol)) continue;
                if (Object.values(all_cards_chars[symbol]).includes(pickedCard.innerText)) {
                    card_symbol = symbol;
                    break;
                }
            }
            console.log("card_symbol", card_symbol);

            let health_points = parseInt(health.innerText) || 0;
            switch (card_symbol) {
                case "heart":
                    console.log("heart", health_points);
                    if (!game_state.hasHealedThisTurn) {
                        health_points = Math.min(20, health_points + card_power);
                        health.innerText = health_points.toString();
                        game_state.hasHealedThisTurn = true;
                    }
                    break;

                case "diamond":
                    console.log("diamond", health_points);
                    game_state.equippedWeapon = { card_power: card_power, lastKill: 15 };
                    weapon_board.innerHTML = "";
                    weapon_board.appendChild(cardElement(cardChar(card_symbol, card_power), "active_weapon_card_template"));
                    console.log("Equipped weapon with card_power:", card_power);
                    break;

                case "black_heart":
                case "tree":
                    console.log("monster", health_points);
                    let damage = card_power;

                    if (!(game_state.equippedWeapon && card_power <= game_state.equippedWeapon.lastKill)) {
                        alert("Weapon too dull!");
                        console.log(card_power, game_state.equippedWeapon, game_state.equippedWeapon.lastKill)
                        return "dull_weapon";
                    }
                    damage = Math.max(0, card_power - game_state.equippedWeapon.card_power);
                    game_state.equippedWeapon.lastKill = card_power; // Weapon degrades
                    weapon_board.appendChild(cardElement(cardChar(card_symbol, card_power), "card_template"));
                    console.log("Used weapon. New ceiling:", card_power);
                    health_points -= damage;
                    health.innerText = health_points.toString();

                    if (health_points <= 0) {
                        alert("Game Over!");
                        window.location.href = "/";
                    }
                    break;
            }

            console.log("health_points_a", health_points);
            pickedCard.innerText = cardChar("blank", "1");
        }

        /**
         * @param {HTMLElement} clickedCard 
         * @returns {void}
         */
        function clickedOneOfFour(clickedCard) {
            if (clickedCard.getAttribute("used_this_turn") === "true") {
                return;
            }

            if (clickedCard.getAttribute("card_type") !== "one_of_four") {
                return;
            }

            game_state.inTheRoom = true;

            const cards_before = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=true]');
            if (cards_before.length < 3) {
                if (clickedCard.matches('.card[card_type=one_of_four][used_this_turn=false]')) {
                    const is_dull_weapon = cardPicked(clickedCard);
                    if (is_dull_weapon === "dull_weapon") {
                        return;
                    }
                }
                game_state.hasHealedThisTurn = false;

                /** @type {NodeListOf<HTMLElement>} */
                const cards_after = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=true]');
                if (cards_after.length === 3) {
                    console.log("cards_after.length", cards_after.length);
                    let time_out = 300;
                    
                    cards_after.forEach((card) => {
                        const random_card_char = randomCardChar();
                        delete deck[random_card_char];

                        const new_card = cardElement(random_card_char, "one_of_four_card_template_not_used");

                        setTimeout(() => {
                            the_deck_card.innerText = random_card_char;
                            four_board.replaceChild(new_card, card);
                        }, time_out);

                        time_out += 300;
                    });

                    time_out += 300;
                    setTimeout(() => {
                        the_deck_card.innerText = cardChar("blank", "1");
                        /** @type {NodeListOf<HTMLElement>} */
                        const cards_reset = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=true]');
                        cards_reset.forEach((card) => {
                            card.setAttribute("used_this_turn", "false");
                        });
                        
                        newRoom()
                    }, time_out);
                }
            }
        }

        function clickedActiveWeapon() {
            if (!game_state.disabledWeapon) {
                game_state.disabledWeapon = game_state.equippedWeapon;
                game_state.equippedWeapon = null;
                weapon_board.setAttribute("bare_handed", "true");
            }
            else {
                game_state.equippedWeapon = game_state.disabledWeapon;
                game_state.disabledWeapon = null;
                weapon_board.setAttribute("bare_handed", "false");
            }
        }

        refreshFourBoard();
        weapon_board.appendChild(cardElement(cardChar("blank", "1"), "card_template"));
    })();
</script>