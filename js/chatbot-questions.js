import { ENUMS } from './enums';

const questions = [
  {
    question: "Bonjour, je suis {assistant}, votre conseiller en ligne.",
  },
  {
    question: "Afin de vous aiguiller, j'ai besoin de différentes informations.",
  },
  {
    question: "Notre engagement : zéro revente de vos données personnelles à des sociétés tierces. Commençons par évaluer vos besoins de santé."
  },
  {
    question: "Quels sont vos besoins en soins médicaux courants ?",
    fieldType: "radio",
    fieldName: "wishes[soins]",
    direction: "column",
    options: [
      { value: "1", label: "Aucun" },
      { value: "2", label: "Peu" },
      { value: "3", label: "Moyen" },
      { value: "4", label: "Élevé" },
      { value: "5", label: "Très élevé" }
    ],
  },
  {
    question: "Quels sont vos besoins en hospitalisation ?",
    fieldType: "radio",
    fieldName: "wishes[hospitalisation]",
    direction: "column",
    options: [
      { value: "1", label: "Aucun" },
      { value: "2", label: "Peu" },
      { value: "3", label: "Moyen" },
      { value: "4", label: "Élevé" },
      { value: "5", label: "Très élevé" }
    ],
  },
  {
    question: "Quels sont vos besoins en optique ?",
    fieldType: "radio",
    fieldName: "wishes[optique]",
    direction: "column",
    options: [
      { value: "1", label: "Aucun" },
      { value: "2", label: "Peu" },
      { value: "3", label: "Moyen" },
      { value: "4", label: "Élevé" },
      { value: "5", label: "Très élevé" }
    ],
  },
  {
    question: "Quels sont vos besoins en dentaire ?",
    fieldType: "radio",
    fieldName: "wishes[dentaire]",
    direction: "column",
    options: [
      { value: "1", label: "Aucun" },
      { value: "2", label: "Peu" },
      { value: "3", label: "Moyen" },
      { value: "4", label: "Élevé" },
      { value: "5", label: "Très élevé" }
    ],
  },
  {
    question: "Avez-vous besoin d'aides auditives ?",
    fieldType: "radio",
    fieldName: "wish_auditive",
    direction: "column",
    options: [
      { value: "1", label: "Aucune" },
      { value: "2", label: "Peu" },
      { value: "3", label: "Moyenne" },
      { value: "4", label: "Élevée" },
      { value: "5", label: "Très élevée" }
    ],
  },
  {
    questions: [
      {
        fieldName: "insureds[{index}][relationship]",
        hidden: true,
        value: "holder",
        showIf: "{index} === 0"
      },
      {
        question: "Quel est votre lien avec cet assuré ?",
        fieldType: "select",
        fieldName: "insureds[{index}][relationship]",
        options: [
          { value: "partner", label: "Conjoint" },
          { value: "child", label: "Enfant" }
        ],
        required: true,
        showIf: "{index} > 0"
      },
      {
        question: {
          0: "Tout d'abord, quel est votre prénom ?",
          other: "Quel est le prénom de cette personne ?"
        },
        fieldType: "text",
        fieldName: "insureds[{index}][firstname]",
        placeholder: {
          0: "Votre prénom",
          other: "Son prénom"
        },
        required: true
      },
      {
        question: {
          0: "Et votre nom de famille ?",
          other: "Et son nom de famille ?"
        },
        fieldType: "text",
        fieldName: "insureds[{index}][lastname]",
        placeholder: {
          0: "Votre nom",
          other: "Son nom"
        },
        required: true
      },
      {
        question: {
          0: "Quelle est votre date de naissance ?",
          other: "Quelle est la date de naissance de cette personne ?"
        },
        fieldType: "date",
        fieldName: "insureds[{index}][birthdate]",
        placeholder: {
          0: "JJ/MM/AAAA",
          other: "JJ/MM/AAAA"
        },
        required: true
      },
      {
        question: {
          0: "Quelle est votre activité professionnelle ?",
          other: "Quelle est l'activité professionnelle de cette personne ?"
        },
        fieldType: "radio",
        fieldName: "insureds[{index}][current_activity]",
        direction: "column",
        options: [
          { value: "employed", label: "En activité" },
          { value: "unemployed", label: "Sans emploi" },
          { value: "retired", label: "Retraité" }
        ],
        required: true
      },
      {
        question: {
          0: "Quel est votre régime de sécurité sociale ?",
          other: "Quel est le régime de sécurité sociale de cette personne ?"
        },
        fieldType: "radio",
        fieldName: "insureds[{index}][affiliate]",
        direction: "column",
        required: true,
        dependsOn: "insureds[{index}][current_activity]",
        optionsSets: {
          employed: [
            { value: "salarie", label: ENUMS.REGIME.salarie },
            { value: "alsace_moselle", label: ENUMS.REGIME.alsace_moselle },
            { value: "agricole", label: ENUMS.REGIME.agricole },
            { value: "salarie_agricole", label: ENUMS.REGIME.salarie_agricole },
            { value: "fonction_publique", label: ENUMS.REGIME.fonction_publique }
          ],
          unemployed: [
            { value: "tns", label: ENUMS.REGIME.tns },
            { value: "etudiant", label: ENUMS.REGIME.etudiant }
          ],
          retired: [
            { value: "retraite_salarie", label: ENUMS.REGIME.retraite_salarie },
            { value: "retraite_tns", label: ENUMS.REGIME.retraite_tns },
            { value: "retraite_alsace_moselle", label: ENUMS.REGIME.retraite_alsace_moselle }
          ]
        }
      },
      {
        question: {
          0: "Souhaitez-vous ajouter une autre personne à assurer ?",
          other: "Souhaitez-vous ajouter une autre personne à assurer ?"
        },
        fieldType: "radio",
        fieldName: "insureds[{index}][add_another]",
        options: [
          { value: "true", label: "Oui" },
          { value: "false", label: "Non" }
        ],
        required: true,
        addItem: true
      }
    ]
  },
  {
    question: "Quel est votre numéro de téléphone ?",
    fieldType: "tel",
    fieldName: "contact[phones][0]",
    placeholder: "Votre numéro de téléphone",
    required: true
  },
  {
    question: "Quelle est votre adresse email ?",
    fieldType: "email",
    fieldName: "contact[email]",
    placeholder: "Votre email",
    required: true
  },
  {
    question: "Quelle est votre adresse postale ?",
    fieldType: "text",
    fieldName: "contact[postal_code]",
    placeholder: "Votre adresse postale",
    required: true
  },
  {
    question: "Voulez-vous contacter directement un conseiller?",
    fieldName: 'contact_advisor',
    fieldType: 'radio',
    dependsOn: 'contact[postal_code]',
    startsWith: '76',
    options: [
      { value: 'yes', label: 'Oui' },
      { value: 'no', label: 'Non' }
    ],
    required: true
  },
  {
    question: "Vous pouvez nous joindre par téléphone au <a href='tel:0800 000 000'>0800 000 000</a> ou par email à <a href='mailto:example@test.com'>example@test.com</a>.",
    dependsOn: 'contact_advisor',
    startsWith: 'yes',
    break: true
  },
  {
    question: "Dans quelle ville habitez-vous ?",
    fieldType: "text",
    fieldName: "contact[locality]",
    placeholder: "Votre ville",
    required: true
  },
  {
    question: "À partir de quelle date souhaitez-vous être couvert ?",
    fieldType: "date",
    fieldName: "effective_date",
    placeholder: "JJ/MM/AAAA",
    required: true,
    dateStartFrom: "today"
  },
  {
    question: "Souhaitez-vous une nouvelle mutuelle ou remplacer votre contrat actuel ?",
    fieldType: "radio",
    fieldName: "need",
    direction: "column",
    options: [
      { value: "new", label: ENUMS.NEED.new },
      { value: "change", label: ENUMS.NEED.change }
    ],
    required: true
  },
  {
    question: "En soumettant ce formulaire, vous acceptez que vos données personnelles soient traitées conformément au Règlement Général sur la Protection des Données (RGPD) et à notre politique de confidentialité. Les informations que vous fournissez seront utilisées uniquement dans le cadre de votre demande de comparaison de mutuelles et pour vous contacter par téléphone afin de vous fournir des informations complémentaires et des devis personnalisés. Acceptez-vous ?",
    fieldType: "radio",
    fieldName: "consent",
    options: [
      { value: "1", label: "Oui" },
      { value: "0", label: "Non" }
    ],
    required: true,
    skipTyping: true
  },
  {
    questions: [
      {
        fieldName: "contact[lastname]",
        hidden: true,
        copyFrom: "insureds[0][lastname]"
      },
      {
        fieldName: "contact[firstname]",
        hidden: true,
        copyFrom: "insureds[0][firstname]"
      },
      {
        fieldName: "contact[civility]",
        hidden: true,
        copyFrom: "insureds[0][civility]"
      },
      {
        fieldName: "utm_source",
        hidden: true,
        value: () => {
          const urlParams = new URLSearchParams(window.location.search);
          console.log(urlParams.get('utm_source'));
          return urlParams.get('utm_source') || 'website';
        }
      },
      {
        fieldName: "utm_medium",
        hidden: true,
        value: () => {
          const urlParams = new URLSearchParams(window.location.search);
          return urlParams.get('utm_medium') || '';
        }
      },
      {
        fieldName: "utm_campaign",
        hidden: true,
        value: () => {
          const urlParams = new URLSearchParams(window.location.search);
          return urlParams.get('utm_campaign') || '';
        }
      },
      {
        fieldName: "utm_term",
        hidden: true,
        value: () => {
          const urlParams = new URLSearchParams(window.location.search);
          return urlParams.get('utm_term') || '';
        }
      },
      {
        fieldName: "utm_content",
        hidden: true,
        value: () => {
          const urlParams = new URLSearchParams(window.location.search);
          return urlParams.get('utm_content') || '';
        }
      },
      {
        fieldName: "note",
        hidden: true,
        value: (formData) => {
          const { location } = document;
          return `Aides auditives: ${formData['wish_auditive'] || '0'}, Site: ${location.hostname}, Source: ${new URLSearchParams(location.search).get('utm_source') || 'website'}`;
        }
      }
    ]
  },
  {
    submit: true,
    submitMessage: "Je transmets votre dossier à un expert santé qui vous contactera dans les plus brefs délais.",
    submitMessageFailed: "Une erreur est survenue lors de la soumission de votre dossier. Veuillez réessayer plus tard."
  }
];

export default questions;
