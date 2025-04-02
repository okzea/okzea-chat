export class FormSubmission {
  submit(formData, successCallback) {
    const data = {};
    
    formData.forEach((value, key) => {
      const keys = key.split('[').map(k => k.replace(']', ''));
      keys.reduce((acc, k, i) => {
        if (i === keys.length - 1) {
          acc[k] = value;
        } else {
          acc[k] = acc[k] || {};
        }
        return acc[k];
      }, data);
    });

    fetch('/wp-json/okzea-chatbot/v1/submit', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    })
      .then(response => response.json())
      .then(data => {
        if (data.submission_id) {
          console.log('Submission successful! ID: ' + data.submission_id);
          successCallback(data);
        } else {
          alert('Submission failed: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
  }
}
