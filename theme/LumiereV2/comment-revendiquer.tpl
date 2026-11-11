<div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <div style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 40px; border: 1px solid #e2e8f0;">
        <h1 style="color: #1e293b; font-size: 2.5rem; font-weight: 800; margin-bottom: 20px; text-align: center;">Comment revendiquer votre fiche ?</h1>
        <p style="color: #64748b; font-size: 1.1rem; text-align: center; margin-bottom: 50px;">Suivez ces étapes simples pour prendre le contrôle de votre visibilité locale.</p>

        <div class="steps-container">
            {# Étape 1 #}
            <div style="display: flex; gap: 30px; margin-bottom: 60px; align-items: start;">
                <div style="flex-shrink: 0; width: 50px; height: 50px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; box-shadow: 0 4px 10px rgba(59,130,246,0.3);">1</div>
                <div style="flex-grow: 1;">
                    <h3 style="color: #1e293b; font-size: 1.4rem; margin-bottom: 10px;">Recherchez votre établissement</h3>
                    <p style="color: #475569; line-height: 1.6;">Utilisez le moteur de recherche sur la page d'accueil pour trouver votre commerce ou votre association. Tapez simplement son nom ou son adresse.</p>
                    <div style="margin-top: 20px; background: #f8fafc; border-radius: 12px; padding: 10px; border: 1px solid #e2e8f0;">
                        <img src="/theme/LumiereV2/header.jpg" alt="Recherche" style="width: 100%; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <p style="font-size: 0.8rem; color: #94a3b8; text-align: center; margin-top: 8px;">Tapez le nom dans la barre de recherche</p>
                    </div>
                </div>
            </div>

            {# Étape 2 #}
            <div style="display: flex; gap: 30px; margin-bottom: 60px; align-items: start;">
                <div style="flex-shrink: 0; width: 50px; height: 50px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; box-shadow: 0 4px 10px rgba(59,130,246,0.3);">2</div>
                <div style="flex-grow: 1;">
                    <h3 style="color: #1e293b; font-size: 1.4rem; margin-bottom: 10px;">Cliquez sur "C'est mon établissement"</h3>
                    <p style="color: #475569; line-height: 1.6;">Une fois sur votre fiche, recherchez le bouton de revendication. Il se trouve généralement sous le nom ou dans les options de la fiche.</p>
                    <div style="margin-top: 20px; background: #f8fafc; border-radius: 12px; padding: 20px; text-align: center; border: 1px dashed #cbd5e1;">
                        <button class="btn primary" style="pointer-events: none;"><i class="fa-solid fa-crown"></i> C'est mon établissement !</button>
                    </div>
                </div>
            </div>

            {# Étape 3 #}
            <div style="display: flex; gap: 30px; margin-bottom: 60px; align-items: start;">
                <div style="flex-shrink: 0; width: 50px; height: 50px; background: #8b5cf6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; box-shadow: 0 4px 10px rgba(139,92,246,0.3);">3</div>
                <div style="flex-grow: 1;">
                    <h3 style="color: #1e293b; font-size: 1.4rem; margin-bottom: 10px;">Créez votre compte Pro</h3>
                    <p style="color: #475569; line-height: 1.6;">Pour gérer votre fiche, vous devez posséder un compte. Si vous n'en avez pas, vous pourrez en créer un en quelques secondes. C'est gratuit et cela vous permet de protéger vos informations.</p>
                </div>
            </div>

            {# Alternative #}
            <div style="background: rgba(59,130,246,0.05); border-radius: 16px; padding: 30px; border: 1px solid rgba(59,130,246,0.1);">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <i class="fa-solid fa-circle-question" style="font-size: 1.5rem; color: #3b82f6;"></i>
                    <h3 style="color: #1e293b; font-size: 1.2rem; margin: 0;">Votre établissement n'existe pas encore ?</h3>
                </div>
                <p style="color: #475569; margin-bottom: 20px;">Si vous ne trouvez pas votre commerce dans la recherche, vous pouvez le proposer via notre formulaire de contribution. Une fois validé, il vous sera automatiquement attribué.</p>
                <a href="{{ ROUTER.generate('poi-edit-start') }}" class="btn">Proposer un nouveau lieu</a>
            </div>
        </div>

        <div style="margin-top: 50px; text-align: center;">
            <a href="{{ ROUTER.generate('SearchUi-home') }}" class="btn primary big">Commencer la recherche <i class="fa-solid fa-magnifying-glass" style="margin-left: 10px;"></i></a>
        </div>
    </div>
</div>
