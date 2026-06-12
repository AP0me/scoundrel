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
            <div id="the_deck_card" class="card just align">🂠</div>
        </div>
        <div id="four_board" class="just"></div>
        <div id="weapon_board" class="just"></div>
    </div>
    </div>
</body>

</html>

<script>
    (async () => {
        const response = await fetch("./cards.json");
        const all_cards_chars = await response.json();

        window.cardClicked = function (clickedCard) {
            const clickedCardType = clickedCard.getAttribute("card_type");
            console.log("clickedCardType", clickedCardType);
            switch (clickedCardType) {
                case "one_of_four":
                    clickedOneOfFour(clickedCard);
                    break;
                case "active_weapon":
                    clickedActiveWeapon(clickedCard);
                    break;
            
                default:
                    break;
            }
        }

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        function shuffledDeck(all_cards_chars) {
            let deck = [];
            for (const card_symbol in all_cards_chars) {
                if (!Object.hasOwn(all_cards_chars, card_symbol)) continue;
                const symbol_cards = all_cards_chars[card_symbol];

                for (const card_power in symbol_cards) {
                    if (!Object.hasOwn(symbol_cards, card_power)) continue;

                    const card_char = symbol_cards[card_power];
                    deck[card_char] = card_power;
                }
            }

            shuffleArray(deck)
            return deck;
        }

        let deck = shuffledDeck(all_cards_chars);

        function cardElement(card_char, template_id) {
            const template = document.querySelector("#" + template_id);
            const fragment = template.content.cloneNode(true);
            const cardDiv = fragment.querySelector('.card');
            cardDiv.innerText = card_char;
            return fragment;
        }

        function cardChar(card_symbol, card_power) {
            if (!all_cards_chars[card_symbol] || !all_cards_chars[card_symbol][card_power]) {
                throw new Error("card_symbol or card_power not passed");
            }
            return all_cards_chars[card_symbol][card_power];
        }

        function randomCardChar() {
            const deck_keys = Object.keys(deck);
            const random_card_index = Math.floor(Math.random() * (deck_keys.length - 1));
            const random_card = deck_keys[random_card_index];
            return random_card;
        }

        function refreshFourBoard() {
            four_board.innerHTML = "";
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
            four_board.appendChild(cardElement(randomCardChar(), "one_of_four_card_template_used"));
        }

        function oneOfFourCards() {
            const cards = document.querySelectorAll('.card[card_type=one_of_four]');
            const one_of_four_card = [];
            for (const card_index in cards) {
                if (!Object.hasOwn(cards, card_index)) continue;
                one_of_four_card.push(cards[card_index]);
            }

            return one_of_four_card;
        }

        function getCardType(char) {
            let card_symbol = null;
            let card_power = null;
            for (const symbol in all_cards_chars) {
                if (!Object.hasOwn(all_cards_chars, symbol)) continue;

                card_power = deck[pickedCard.innerText]
                if (all_cards_chars[symbol][card_power]) {
                    card_symbol = symbol;
                    break;
                }
            }
        }

        function cardPicked(pickedCard) {
            pickedCard.setAttribute("used_this_turn", "true");
            let card_power = shuffledDeck(all_cards_chars)[pickedCard.innerText];
            let card_symbol = null;
            for (const symbol in all_cards_chars) {
                if (!Object.hasOwn(all_cards_chars, symbol)) continue;

                if (Object.values(all_cards_chars[symbol]).includes(pickedCard.innerText)) {
                    card_symbol = symbol;
                    break;
                }
            }
            console.log("card_symbol", card_symbol);

            let health_points = health.innerText;
            switch (card_symbol) {
                case "heart":
                    console.log("heart", health_points);
                    if (!window.hasHealedThisTurn) {
                        health_points = Math.min(20, health_points + card_power);
                        health.innerText = health_points;
                        window.hasHealedThisTurn = true;
                    }
                    break;

                case "diamond":
                    console.log("diamond", health_points);
                    window.equippedWeapon = { card_power: card_power, lastKill: 15 };
                    weapon_board.innerHTML = "";
                    weapon_board.appendChild(cardElement(cardChar(card_symbol, card_power), "active_weapon_card_template"));
                    console.log("Equipped weapon with card_power:", card_power);
                    break;

                case "black_heart":
                case "tree":
                    console.log("monster", health_points);
                    let damage = card_power;

                    if (window.equippedWeapon) {
                        if (card_power <= window.equippedWeapon.lastKill) {
                            const reduction = Math.min(card_power, window.equippedWeapon.card_power);
                            damage = Math.max(0, card_power - window.equippedWeapon.card_power);
                            window.equippedWeapon.lastKill = card_power; // Weapon degrades
                            weapon_board.appendChild(cardElement(cardChar(card_symbol, card_power), "card_template"));
                            console.log("Used weapon. New ceiling:", card_power);
                        } else {
                            alert("Weapon too dull!");
                            return;
                        }
                    }
                    health_points -= damage;
                    health.innerText = health_points;
                    if (health_points <= 0) {
                        alert("Game Over!");
                        window.location = "/"
                    };
                    break;
            }

            console.log("health_points_a", health_points);

            pickedCard.innerText = cardChar("blank", "1");
        }

        function clickedOneOfFour(clickedCard) {
            if (clickedCard.getAttribute("used_this_turn") === "true") {
                return;
            }

            if (clickedCard.getAttribute("card_type") !== "one_of_four") {
                return;
            }

            const cards_before = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=true]');
            if (cards_before.length < 3) {
                if (clickedCard.matches('.card[card_type=one_of_four][used_this_turn=false]')) {
                    cardPicked(clickedCard);
                }
                window.hasHealedThisTurn = false;

                const cards_after = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=true]');
                if (cards_after.length === 3) {
                    console.log("cards_after.length", cards_after.length);
                    let time_out = 300;
                    for (const key in cards_after) {
                        if (!Object.hasOwn(cards_after, key)) continue;
                        const card = cards_after[key];
                        
                        const random_card_char = randomCardChar();
                        delete deck[random_card_char];

                        const new_card = cardElement(random_card_char, "one_of_four_card_template_not_used");

                        setTimeout(() => {
                            the_deck_card.innerText = random_card_char;
                            four_board.replaceChild(new_card, card);
                        }, time_out);

                        time_out += 300
                    }

                    time_out += 300
                    setTimeout(() => {
                        the_deck_card.innerText = cardChar("blank", "1");
                        const cards_after = document.querySelectorAll('.card[card_type=one_of_four][used_this_turn=true]');
                        for (const key in cards_after) {
                            if (!Object.hasOwn(cards_after, key)) continue;
                            const card = cards_after[key];
                            card.setAttribute("used_this_turn", "false");
                        }
                    }, time_out);
                }
            }
        }

        function clickedActiveWeapon(clickedCard) {
            if (!window.disabledWeapon) {
                window.disabledWeapon = window.equippedWeapon;
                window.equippedWeapon = null;
                weapon_board.setAttribute("bare_handed", "true");
            }
            else {
                window.equippedWeapon = window.disabledWeapon;
                window.disabledWeapon = null;
                weapon_board.setAttribute("bare_handed", "false");
            }

        }

        refreshFourBoard();

        weapon_board.appendChild(cardElement(cardChar("blank", "1"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "2"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "3"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "4"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "5"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "6"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "7"), "card_template"));
        // weapon_board.appendChild(cardElement(cardChar("heart", "8"), "card_template"));
    })();
</script>