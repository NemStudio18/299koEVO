<section class="module-card">
    <header>
        <i class="fa-solid fa-tag"></i>
        {% if tag %}
            Modifier un tag personnalisé
        {% else %}
            Ajouter un tag personnalisé
        {% endif %}
    </header>

    <form method="post" action="{{ saveUrl }}">
        {% if hasTag %}
        <input type="hidden" name="id" value="{{ tag.id }}">
        {% endif %}

        <div class="field">
            <label for="key_name">Nom de la clé *</label>
            <input type="text" id="key_name" name="key_name" value="{% if hasTag %}{{ tag.key_name }}{% endif %}" 
                   placeholder="ex: accepts_dogs, air_conditioned" required
                   {% if hasTag %}readonly style="background: #f0f0f0;"{% endif %}>
            <small>Identifiant unique du tag (ne peut pas être modifié après création). Utilisez des underscores, pas d'espaces.</small>
        </div>

        <div class="field">
            <label for="label">Label (nom affiché)</label>
            <input type="text" id="label" name="label" value="{% if hasTag and tag.has_label %}{{ tag.label }}{% endif %}" 
                   placeholder="ex: Accepte les chiens">
            <small>Nom lisible qui sera affiché aux propriétaires</small>
        </div>

        <div class="field">
            <label for="emoji">Emoji</label>
            <select id="emoji" name="emoji" style="font-size: 1.5em; padding: 5px;">
                <option value="">-- Aucun emoji --</option>
                
                <optgroup label="Animaux">
                    <option value="🐕" {% if hasTag and tag.has_emoji and tag.emoji == "🐕" %}selected{% endif %}>🐕 Chien</option>
                    <option value="🐈" {% if hasTag and tag.has_emoji and tag.emoji == "🐈" %}selected{% endif %}>🐈 Chat</option>
                    <option value="🐦" {% if hasTag and tag.has_emoji and tag.emoji == "🐦" %}selected{% endif %}>🐦 Oiseau</option>
                    <option value="🐰" {% if hasTag and tag.has_emoji and tag.emoji == "🐰" %}selected{% endif %}>🐰 Lapin</option>
                </optgroup>
                
                <optgroup label="Services & Équipements">
                    <option value="❄️" {% if hasTag and tag.has_emoji and tag.emoji == "❄️" %}selected{% endif %}>❄️ Climatisation</option>
                    <option value="🌳" {% if hasTag and tag.has_emoji and tag.emoji == "🌳" %}selected{% endif %}>🌳 Terrasse extérieure</option>
                    <option value="🌞" {% if hasTag and tag.has_emoji and tag.emoji == "🌞" %}selected{% endif %}>🌞 Ensoleillé</option>
                    <option value="🌙" {% if hasTag and tag.has_emoji and tag.emoji == "🌙" %}selected{% endif %}>🌙 Ouvert le soir</option>
                    <option value="🚗" {% if hasTag and tag.has_emoji and tag.emoji == "🚗" %}selected{% endif %}>🚗 Parking</option>
                    <option value="🚲" {% if hasTag and tag.has_emoji and tag.emoji == "🚲" %}selected{% endif %}>🚲 Vélo</option>
                    <option value="♿" {% if hasTag and tag.has_emoji and tag.emoji == "♿" %}selected{% endif %}>♿ Accessible PMR</option>
                    <option value="👶" {% if hasTag and tag.has_emoji and tag.emoji == "👶" %}selected{% endif %}>👶 Enfant</option>
                    <option value="👥" {% if hasTag and tag.has_emoji and tag.emoji == "👥" %}selected{% endif %}>👥 Groupe</option>
                    <option value="💳" {% if hasTag and tag.has_emoji and tag.emoji == "💳" %}selected{% endif %}>💳 Carte bancaire</option>
                    <option value="💰" {% if hasTag and tag.has_emoji and tag.emoji == "💰" %}selected{% endif %}>💰 Payant</option>
                    <option value="🆓" {% if hasTag and tag.has_emoji and tag.emoji == "🆓" %}selected{% endif %}>🆓 Gratuit</option>
                    <option value="📶" {% if hasTag and tag.has_emoji and tag.emoji == "📶" %}selected{% endif %}>📶 WiFi</option>
                    <option value="📱" {% if hasTag and tag.has_emoji and tag.emoji == "📱" %}selected{% endif %}>📱 Mobile</option>
                    <option value="🔒" {% if hasTag and tag.has_emoji and tag.emoji == "🔒" %}selected{% endif %}>🔒 Sécurisé</option>
                    <option value="🔐" {% if hasTag and tag.has_emoji and tag.emoji == "🔐" %}selected{% endif %}>🔐 Privé</option>
                </optgroup>
                
                <optgroup label="Restaurants & Cuisines">
                    <option value="🍽️" {% if hasTag and tag.has_emoji and tag.emoji == "🍽️" %}selected{% endif %}>🍽️ Restaurant</option>
                    <option value="☕" {% if hasTag and tag.has_emoji and tag.emoji == "☕" %}selected{% endif %}>☕ Café</option>
                    <option value="🍺" {% if hasTag and tag.has_emoji and tag.emoji == "🍺" %}selected{% endif %}>🍺 Bar</option>
                    <option value="🍕" {% if hasTag and tag.has_emoji and tag.emoji == "🍕" %}selected{% endif %}>🍕 Pizza</option>
                    <option value="🍔" {% if hasTag and tag.has_emoji and tag.emoji == "🍔" %}selected{% endif %}>🍔 Burger</option>
                    <option value="🌭" {% if hasTag and tag.has_emoji and tag.emoji == "🌭" %}selected{% endif %}>🌭 Hot-dog</option>
                    <option value="🌮" {% if hasTag and tag.has_emoji and tag.emoji == "🌮" %}selected{% endif %}>🌮 Taco</option>
                    <option value="🌯" {% if hasTag and tag.has_emoji and tag.emoji == "🌯" %}selected{% endif %}>🌯 Burrito</option>
                    <option value="🥙" {% if hasTag and tag.has_emoji and tag.emoji == "🥙" %}selected{% endif %}>🥙 Kebab</option>
                    <option value="🍣" {% if hasTag and tag.has_emoji and tag.emoji == "🍣" %}selected{% endif %}>🍣 Sushi</option>
                    <option value="🍜" {% if hasTag and tag.has_emoji and tag.emoji == "🍜" %}selected{% endif %}>🍜 Ramen</option>
                    <option value="🍱" {% if hasTag and tag.has_emoji and tag.emoji == "🍱" %}selected{% endif %}>🍱 Bento</option>
                    <option value="🥘" {% if hasTag and tag.has_emoji and tag.emoji == "🥘" %}selected{% endif %}>🥘 Paella</option>
                    <option value="🍲" {% if hasTag and tag.has_emoji and tag.emoji == "🍲" %}selected{% endif %}>🍲 Pot-au-feu</option>
                    <option value="🥗" {% if hasTag and tag.has_emoji and tag.emoji == "🥗" %}selected{% endif %}>🥗 Salade</option>
                    <option value="🌿" {% if hasTag and tag.has_emoji and tag.emoji == "🌿" %}selected{% endif %}>🌿 Bio/Végétarien</option>
                    <option value="🌶️" {% if hasTag and tag.has_emoji and tag.emoji == "🌶️" %}selected{% endif %}>🌶️ Épicé</option>
                    <option value="🍖" {% if hasTag and tag.has_emoji and tag.emoji == "🍖" %}selected{% endif %}>🍖 Viande</option>
                    <option value="🐟" {% if hasTag and tag.has_emoji and tag.emoji == "🐟" %}selected{% endif %}>🐟 Poisson</option>
                    <option value="🦐" {% if hasTag and tag.has_emoji and tag.emoji == "🦐" %}selected{% endif %}>🦐 Fruits de mer</option>
                    <option value="🍳" {% if hasTag and tag.has_emoji and tag.emoji == "🍳" %}selected{% endif %}>🍳 Brunch</option>
                </optgroup>
                
                <optgroup label="Boulangerie & Pâtisserie">
                    <option value="🥖" {% if hasTag and tag.has_emoji and tag.emoji == "🥖" %}selected{% endif %}>🥖 Baguette</option>
                    <option value="🥐" {% if hasTag and tag.has_emoji and tag.emoji == "🥐" %}selected{% endif %}>🥐 Croissant</option>
                    <option value="🍞" {% if hasTag and tag.has_emoji and tag.emoji == "🍞" %}selected{% endif %}>🍞 Pain</option>
                    <option value="🥨" {% if hasTag and tag.has_emoji and tag.emoji == "🥨" %}selected{% endif %}>🥨 Bretzel</option>
                    <option value="🧁" {% if hasTag and tag.has_emoji and tag.emoji == "🧁" %}selected{% endif %}>🧁 Cupcake</option>
                    <option value="🍰" {% if hasTag and tag.has_emoji and tag.emoji == "🍰" %}selected{% endif %}>🍰 Gâteau</option>
                    <option value="🎂" {% if hasTag and tag.has_emoji and tag.emoji == "🎂" %}selected{% endif %}>🎂 Anniversaire</option>
                    <option value="🍪" {% if hasTag and tag.has_emoji and tag.emoji == "🍪" %}selected{% endif %}>🍪 Cookie</option>
                    <option value="🍩" {% if hasTag and tag.has_emoji and tag.emoji == "🍩" %}selected{% endif %}>🍩 Donut</option>
                    <option value="🥧" {% if hasTag and tag.has_emoji and tag.emoji == "🥧" %}selected{% endif %}>🥧 Tarte</option>
                    <option value="🧇" {% if hasTag and tag.has_emoji and tag.emoji == "🧇" %}selected{% endif %}>🧇 Gaufre</option>
                    <option value="🥞" {% if hasTag and tag.has_emoji and tag.emoji == "🥞" %}selected{% endif %}>🥞 Pancake</option>
                    <option value="🍫" {% if hasTag and tag.has_emoji and tag.emoji == "🍫" %}selected{% endif %}>🍫 Chocolat</option>
                    <option value="🍬" {% if hasTag and tag.has_emoji and tag.emoji == "🍬" %}selected{% endif %}>🍬 Bonbon</option>
                </optgroup>
                
                <optgroup label="Boissons">
                    <option value="🍷" {% if hasTag and tag.has_emoji and tag.emoji == "🍷" %}selected{% endif %}>🍷 Vin</option>
                    <option value="🍻" {% if hasTag and tag.has_emoji and tag.emoji == "🍻" %}selected{% endif %}>🍻 Bière</option>
                    <option value="🍸" {% if hasTag and tag.has_emoji and tag.emoji == "🍸" %}selected{% endif %}>🍸 Cocktail</option>
                    <option value="🥃" {% if hasTag and tag.has_emoji and tag.emoji == "🥃" %}selected{% endif %}>🥃 Whisky</option>
                    <option value="🍹" {% if hasTag and tag.has_emoji and tag.emoji == "🍹" %}selected{% endif %}>🍹 Mojito</option>
                    <option value="🧃" {% if hasTag and tag.has_emoji and tag.emoji == "🧃" %}selected{% endif %}>🧃 Jus</option>
                    <option value="🧉" {% if hasTag and tag.has_emoji and tag.emoji == "🧉" %}selected{% endif %}>🧉 Maté</option>
                    <option value="🥤" {% if hasTag and tag.has_emoji and tag.emoji == "🥤" %}selected{% endif %}>🥤 Soda</option>
                    <option value="🧊" {% if hasTag and tag.has_emoji and tag.emoji == "🧊" %}selected{% endif %}>🧊 Glace</option>
                </optgroup>
                
                <optgroup label="Hébergement">
                    <option value="🛏️" {% if hasTag and tag.has_emoji and tag.emoji == "🛏️" %}selected{% endif %}>🛏️ Hôtel</option>
                    <option value="🏨" {% if hasTag and tag.has_emoji and tag.emoji == "🏨" %}selected{% endif %}>🏨 Hôtel (bâtiment)</option>
                    <option value="🏠" {% if hasTag and tag.has_emoji and tag.emoji == "🏠" %}selected{% endif %}>🏠 Maison d'hôtes</option>
                    <option value="⛺" {% if hasTag and tag.has_emoji and tag.emoji == "⛺" %}selected{% endif %}>⛺ Camping</option>
                    <option value="🚐" {% if hasTag and tag.has_emoji and tag.emoji == "🚐" %}selected{% endif %}>🚐 Camping-car</option>
                </optgroup>
                
                <optgroup label="Shopping & Commerce">
                    <option value="🛍️" {% if hasTag and tag.has_emoji and tag.emoji == "🛍️" %}selected{% endif %}>🛍️ Shopping</option>
                    <option value="🛒" {% if hasTag and tag.has_emoji and tag.emoji == "🛒" %}selected{% endif %}>🛒 Supermarché</option>
                    <option value="🏪" {% if hasTag and tag.has_emoji and tag.emoji == "🏪" %}selected{% endif %}>🏪 Épicerie</option>
                    <option value="👕" {% if hasTag and tag.has_emoji and tag.emoji == "👕" %}selected{% endif %}>👕 Vêtements</option>
                    <option value="👔" {% if hasTag and tag.has_emoji and tag.emoji == "👔" %}selected{% endif %}>👔 Mode</option>
                    <option value="👟" {% if hasTag and tag.has_emoji and tag.emoji == "👟" %}selected{% endif %}>👟 Chaussures</option>
                    <option value="💍" {% if hasTag and tag.has_emoji and tag.emoji == "💍" %}selected{% endif %}>💍 Bijouterie</option>
                    <option value="💄" {% if hasTag and tag.has_emoji and tag.emoji == "💄" %}selected{% endif %}>💄 Cosmétiques</option>
                    <option value="🧴" {% if hasTag and tag.has_emoji and tag.emoji == "🧴" %}selected{% endif %}>🧴 Parfumerie</option>
                    <option value="📱" {% if hasTag and tag.has_emoji and tag.emoji == "📱" %}selected{% endif %}>📱 Électronique</option>
                    <option value="💻" {% if hasTag and tag.has_emoji and tag.emoji == "💻" %}selected{% endif %}>💻 Informatique</option>
                    <option value="📚" {% if hasTag and tag.has_emoji and tag.emoji == "📚" %}selected{% endif %}>📚 Librairie</option>
                    <option value="🎁" {% if hasTag and tag.has_emoji and tag.emoji == "🎁" %}selected{% endif %}>🎁 Cadeau</option>
                    <option value="🎨" {% if hasTag and tag.has_emoji and tag.emoji == "🎨" %}selected{% endif %}>🎨 Artisanat</option>
                    <option value="🌻" {% if hasTag and tag.has_emoji and tag.emoji == "🌻" %}selected{% endif %}>🌻 Fleuriste</option>
                </optgroup>
                
                <optgroup label="Culture & Loisirs">
                    <option value="🎭" {% if hasTag and tag.has_emoji and tag.emoji == "🎭" %}selected{% endif %}>🎭 Théâtre</option>
                    <option value="🎬" {% if hasTag and tag.has_emoji and tag.emoji == "🎬" %}selected{% endif %}>🎬 Cinéma</option>
                    <option value="🎵" {% if hasTag and tag.has_emoji and tag.emoji == "🎵" %}selected{% endif %}>🎵 Musique</option>
                    <option value="🎤" {% if hasTag and tag.has_emoji and tag.emoji == "🎤" %}selected{% endif %}>🎤 Karaoké</option>
                    <option value="🎸" {% if hasTag and tag.has_emoji and tag.emoji == "🎸" %}selected{% endif %}>🎸 Concert</option>
                    <option value="🎹" {% if hasTag and tag.has_emoji and tag.emoji == "🎹" %}selected{% endif %}>🎹 Piano</option>
                    <option value="🎨" {% if hasTag and tag.has_emoji and tag.emoji == "🎨" %}selected{% endif %}>🎨 Art</option>
                    <option value="🖼️" {% if hasTag and tag.has_emoji and tag.emoji == "🖼️" %}selected{% endif %}>🖼️ Galerie</option>
                    <option value="📚" {% if hasTag and tag.has_emoji and tag.emoji == "📚" %}selected{% endif %}>📚 Bibliothèque</option>
                    <option value="📖" {% if hasTag and tag.has_emoji and tag.emoji == "📖" %}selected{% endif %}>📖 Livre</option>
                    <option value="🎪" {% if hasTag and tag.has_emoji and tag.emoji == "🎪" %}selected{% endif %}>🎪 Cirque</option>
                    <option value="🎯" {% if hasTag and tag.has_emoji and tag.emoji == "🎯" %}selected{% endif %}>🎯 Activité</option>
                    <option value="🎲" {% if hasTag and tag.has_emoji and tag.emoji == "🎲" %}selected{% endif %}>🎲 Jeux</option>
                    <option value="🃏" {% if hasTag and tag.has_emoji and tag.emoji == "🃏" %}selected{% endif %}>🃏 Casino</option>
                </optgroup>
                
                <optgroup label="Sport & Fitness">
                    <option value="🏃" {% if hasTag and tag.has_emoji and tag.emoji == "🏃" %}selected{% endif %}>🏃 Course</option>
                    <option value="🏋️" {% if hasTag and tag.has_emoji and tag.emoji == "🏋️" %}selected{% endif %}>🏋️ Fitness</option>
                    <option value="🏊" {% if hasTag and tag.has_emoji and tag.emoji == "🏊" %}selected{% endif %}>🏊 Piscine</option>
                    <option value="🏄" {% if hasTag and tag.has_emoji and tag.emoji == "🏄" %}selected{% endif %}>🏄 Surf</option>
                    <option value="🏂" {% if hasTag and tag.has_emoji and tag.emoji == "🏂" %}selected{% endif %}>🏂 Snowboard</option>
                    <option value="⛷️" {% if hasTag and tag.has_emoji and tag.emoji == "⛷️" %}selected{% endif %}>⛷️ Ski</option>
                    <option value="🎾" {% if hasTag and tag.has_emoji and tag.emoji == "🎾" %}selected{% endif %}>🎾 Tennis</option>
                    <option value="⚽" {% if hasTag and tag.has_emoji and tag.emoji == "⚽" %}selected{% endif %}>⚽ Football</option>
                    <option value="🏀" {% if hasTag and tag.has_emoji and tag.emoji == "🏀" %}selected{% endif %}>🏀 Basketball</option>
                    <option value="🏐" {% if hasTag and tag.has_emoji and tag.emoji == "🏐" %}selected{% endif %}>🏐 Volley</option>
                    <option value="🏓" {% if hasTag and tag.has_emoji and tag.emoji == "🏓" %}selected{% endif %}>🏓 Ping-pong</option>
                    <option value="🏸" {% if hasTag and tag.has_emoji and tag.emoji == "🏸" %}selected{% endif %}>🏸 Badminton</option>
                    <option value="⛳" {% if hasTag and tag.has_emoji and tag.emoji == "⛳" %}selected{% endif %}>⛳ Golf</option>
                    <option value="🏇" {% if hasTag and tag.has_emoji and tag.emoji == "🏇" %}selected{% endif %}>🏇 Équitation</option>
                    <option value="🚴" {% if hasTag and tag.has_emoji and tag.emoji == "🚴" %}selected{% endif %}>🚴 VTT</option>
                    <option value="🧘" {% if hasTag and tag.has_emoji and tag.emoji == "🧘" %}selected{% endif %}>🧘 Yoga</option>
                    <option value="🤸" {% if hasTag and tag.has_emoji and tag.emoji == "🤸" %}selected{% endif %}>🤸 Gymnastique</option>
                </optgroup>
                
                <optgroup label="Santé & Bien-être">
                    <option value="🏥" {% if hasTag and tag.has_emoji and tag.emoji == "🏥" %}selected{% endif %}>🏥 Hôpital</option>
                    <option value="🏩" {% if hasTag and tag.has_emoji and tag.emoji == "🏩" %}selected{% endif %}>🏩 Clinique</option>
                    <option value="💊" {% if hasTag and tag.has_emoji and tag.emoji == "💊" %}selected{% endif %}>💊 Pharmacie</option>
                    <option value="🦷" {% if hasTag and tag.has_emoji and tag.emoji == "🦷" %}selected{% endif %}>🦷 Dentiste</option>
                    <option value="👁️" {% if hasTag and tag.has_emoji and tag.emoji == "👁️" %}selected{% endif %}>👁️ Ophtalmologiste</option>
                    <option value="💆" {% if hasTag and tag.has_emoji and tag.emoji == "💆" %}selected{% endif %}>💆 Massage</option>
                    <option value="💇" {% if hasTag and tag.has_emoji and tag.emoji == "💇" %}selected{% endif %}>💇 Coiffeur</option>
                    <option value="💅" {% if hasTag and tag.has_emoji and tag.emoji == "💅" %}selected{% endif %}>💅 Manucure</option>
                    <option value="🧴" {% if hasTag and tag.has_emoji and tag.emoji == "🧴" %}selected{% endif %}>🧴 Spa</option>
                    <option value="🧘" {% if hasTag and tag.has_emoji and tag.emoji == "🧘" %}selected{% endif %}>🧘 Bien-être</option>
                </optgroup>
                
                <optgroup label="Événements & Fêtes">
                    <option value="🎉" {% if hasTag and tag.has_emoji and tag.emoji == "🎉" %}selected{% endif %}>🎉 Fête</option>
                    <option value="🎂" {% if hasTag and tag.has_emoji and tag.emoji == "🎂" %}selected{% endif %}>🎂 Anniversaire</option>
                    <option value="💒" {% if hasTag and tag.has_emoji and tag.emoji == "💒" %}selected{% endif %}>💒 Mariage</option>
                    <option value="🎄" {% if hasTag and tag.has_emoji and tag.emoji == "🎄" %}selected{% endif %}>🎄 Noël</option>
                    <option value="🎃" {% if hasTag and tag.has_emoji and tag.emoji == "🎃" %}selected{% endif %}>🎃 Halloween</option>
                    <option value="🎊" {% if hasTag and tag.has_emoji and tag.emoji == "🎊" %}selected{% endif %}>🎊 Confetti</option>
                    <option value="🎈" {% if hasTag and tag.has_emoji and tag.emoji == "🎈" %}selected{% endif %}>🎈 Ballon</option>
                    <option value="🎪" {% if hasTag and tag.has_emoji and tag.emoji == "🎪" %}selected{% endif %}>🎪 Événement</option>
                </optgroup>
                
                <optgroup label="Statuts & Recommandations">
                    <option value="⭐" {% if hasTag and tag.has_emoji and tag.emoji == "⭐" %}selected{% endif %}>⭐ Recommandé</option>
                    <option value="❤️" {% if hasTag and tag.has_emoji and tag.emoji == "❤️" %}selected{% endif %}>❤️ Coup de cœur</option>
                    <option value="🔥" {% if hasTag and tag.has_emoji and tag.emoji == "🔥" %}selected{% endif %}>🔥 Populaire</option>
                    <option value="✨" {% if hasTag and tag.has_emoji and tag.emoji == "✨" %}selected{% endif %}>✨ Nouveau</option>
                    <option value="🎯" {% if hasTag and tag.has_emoji and tag.emoji == "🎯" %}selected{% endif %}>🎯 Top</option>
                    <option value="🏆" {% if hasTag and tag.has_emoji and tag.emoji == "🏆" %}selected{% endif %}>🏆 Meilleur</option>
                    <option value="💎" {% if hasTag and tag.has_emoji and tag.emoji == "💎" %}selected{% endif %}>💎 Premium</option>
                </optgroup>
                
                <optgroup label="Contact & Communication">
                    <option value="📞" {% if hasTag and tag.has_emoji and tag.emoji == "📞" %}selected{% endif %}>📞 Réservation</option>
                    <option value="📧" {% if hasTag and tag.has_emoji and tag.emoji == "📧" %}selected{% endif %}>📧 Email</option>
                    <option value="🌐" {% if hasTag and tag.has_emoji and tag.emoji == "🌐" %}selected{% endif %}>🌐 Site web</option>
                    <option value="📱" {% if hasTag and tag.has_emoji and tag.emoji == "📱" %}selected{% endif %}>📱 Application</option>
                    <option value="💬" {% if hasTag and tag.has_emoji and tag.emoji == "💬" %}selected{% endif %}>💬 Chat</option>
                </optgroup>
                
                <optgroup label="Divers">
                    <option value="🌍" {% if hasTag and tag.has_emoji and tag.emoji == "🌍" %}selected{% endif %}>🌍 International</option>
                    <option value="🇫🇷" {% if hasTag and tag.has_emoji and tag.emoji == "🇫🇷" %}selected{% endif %}>🇫🇷 Français</option>
                    <option value="🌆" {% if hasTag and tag.has_emoji and tag.emoji == "🌆" %}selected{% endif %}>🌆 Urbain</option>
                    <option value="🌄" {% if hasTag and tag.has_emoji and tag.emoji == "🌄" %}selected{% endif %}>🌄 Montagne</option>
                    <option value="🏖️" {% if hasTag and tag.has_emoji and tag.emoji == "🏖️" %}selected{% endif %}>🏖️ Plage</option>
                    <option value="⛰️" {% if hasTag and tag.has_emoji and tag.emoji == "⛰️" %}selected{% endif %}>⛰️ Nature</option>
                </optgroup>
            </select>
            <small>Emoji à afficher avec le tag (optionnel)</small>
        </div>

        <div class="field">
            <label for="default_value">Valeur par défaut</label>
            <input type="text" id="default_value" name="default_value" value="{% if hasTag %}{{ tag.default_value }}{% else %}yes{% endif %}" 
                   placeholder="ex: yes, no, ou texte libre">
            <small>Valeur qui sera assignée au tag quand il est sélectionné (par défaut: "yes")</small>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Description du tag pour aider les propriétaires">{% if hasTag and tag.has_description %}{{ tag.description }}{% endif %}</textarea>
            <small>Description optionnelle qui sera affichée sous le tag</small>
        </div>

        <div class="field">
            <label>
                <input type="checkbox" name="is_active" value="1" {% if hasTag %}{% if tag.is_active %}checked{% endif %}{% else %}checked{% endif %}>
                Tag actif
            </label>
            <small>Les tags inactifs ne seront pas affichés aux propriétaires</small>
        </div>

        <div class="field-row">
            <button type="submit" class="button primary">
                <i class="fa-solid fa-save"></i> Enregistrer
            </button>
            <a href="{{ cancelUrl }}" class="button">
                <i class="fa-solid fa-times"></i> Annuler
            </a>
        </div>
    </form>
</section>

