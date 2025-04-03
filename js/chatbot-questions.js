import { ENUMS } from './enums';

const questions = [
  {
    question: "Hello, I'm your online website creation assistant.",
  },
  {
    question: "Please tell me about you and your website needs so I can help you create the perfect website.",
  },
  {
    question: "What is your name?",
    fieldType: "text",
    fieldName: "contact[name]",
    placeholder: "Your name",
    required: true
  },
  {
    question: "What is your email address?",
    fieldType: "email",
    fieldName: "contact[email]",
    placeholder: "Your email",
    required: true
  },
  {
    question: "What is your phone number?",
    fieldType: "tel",
    fieldName: "contact[phone]",
    placeholder: "Your phone number"
  },
  {
    question: "Do you already have a website?",
    fieldType: "radio",
    fieldName: "has_website",
    options: [
      { value: "yes", label: "Yes" },
      { value: "no", label: "No" }
    ],
    required: true
  },
  {
    question: "Do you already own a domain name for the website you want to build?",
    fieldType: "text",
    fieldName: "domain_name",
    placeholder: "Your domain name or 'No'",
    dependsOn: "has_website",
    startsWith: "no"
  },
  {
    question: "What's the url of your website?",
    fieldType: "text",
    fieldName: "website_url",
    placeholder: "Your website url",
    dependsOn: "has_website",
    startsWith: "yes"
  },
  {
    question: "Please describe the type of business you are running and if there is anything particular you will need on your website.",
    fieldType: "textarea",
    fieldName: "business_description",
    placeholder: "Tell us about your business and website needs"
  },
  {
    question: "Do you already have branding elements for your company such as logo, colors and typography elements?",
    fieldType: "radio",
    fieldName: "has_branding",
    options: [
      { value: "yes", label: "Yes" },
      { value: "no", label: "No" }
    ]
  },
  {
    question: "What should be the primary color of your website?",
    fieldType: "text",
    fieldName: "primary_color",
    placeholder: "E.g., #FF5733, blue, etc."
  },
  {
    question: "What should be the secondary or accent colors of your website?",
    fieldType: "text",
    fieldName: "secondary_colors",
    placeholder: "E.g., #33FF57, red, etc."
  },
  {
    question: "Do you have a specific typography?",
    fieldType: "text",
    fieldName: "typography",
    placeholder: "E.g., Roboto, Arial, etc."
  },
  {
    question: "Please share a website that you like and would like your own website to be inspired by (Website 1)",
    fieldType: "text",
    fieldName: "reference_website_1",
    placeholder: "Website URL"
  },
  {
    question: "Please share another website that you like (Website 2)",
    fieldType: "text",
    fieldName: "reference_website_2",
    placeholder: "Website URL"
  },
  {
    question: "Please share one more website that you like (Website 3)",
    fieldType: "text",
    fieldName: "reference_website_3",
    placeholder: "Website URL"
  },
  {
    question: "How should your website feel?",
    fieldType: "radio",
    fieldName: "website_feel",
    direction: "column",
    options: [
      { value: "adventurous", label: "Adventurous" },
      { value: "dark", label: "Dark" },
      { value: "exotic", label: "Exotic" },
      { value: "light", label: "Light" },
      { value: "luxurious", label: "Luxurious" },
      { value: "minimalistic", label: "Minimalistic" },
      { value: "modern", label: "Modern" }
    ]
  },
  {
    question: "Is there anything else you would like to share?",
    fieldType: "textarea",
    fieldName: "additional_info",
    placeholder: "Additional information"
  },
  {
    question: "How urgently do you need your website?",
    fieldType: "radio",
    fieldName: "urgency",
    direction: "column",
    options: [
      { value: "asap", label: "As soon as possible" },
      { value: "2-3-weeks", label: "Within 2 to 3 weeks" },
      { value: "no-hurry", label: "I'm in no hurry" }
    ]
  },
  {
    submit: true,
    submitMessage: "Thank you for providing your information. We'll review your website request and get back to you soon!",
    submitMessageFailed: "An error occurred while submitting your request. Please try again later."
  }
];

export default questions;
