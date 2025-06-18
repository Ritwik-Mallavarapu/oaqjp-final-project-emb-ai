let RunSentimentAnalysis = ()=>{
    textToAnalyze = document.getElementById("textToAnalyze").value;

    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("system_response").innerHTML = xhttp.responseText;
        }
    };
    xhttp.open("GET", "emotionDetector?textToAnalyze"+"="+textToAnalyze, true);
    xhttp.send();
}
// CHATBOT_JS_START

    document.addEventListener('DOMContentLoaded', function () {
        const chatQueryInput = document.getElementById('chatQueryInput');
        const sendChatQueryBtn = document.getElementById('sendChatQueryBtn');
        const chatDisplay = document.getElementById('chat-display');

        if (sendChatQueryBtn) {
            sendChatQueryBtn.addEventListener('click', function () {
                const query = chatQueryInput.value.trim();
                if (query) {
                    appendMessageToChat('You', query);
                    chatQueryInput.value = '';
                    fetchChatbotResponse(query);
                }
            });
        }

        if (chatQueryInput) {
            chatQueryInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    sendChatQueryBtn.click();
                }
            });
        }

        function appendMessageToChat(sender, message) { // Renamed to avoid conflict if other appendMessage exists
            if (!chatDisplay) return; // Guard clause
            const messageElement = document.createElement('div');
            // Sanitize sender and message if they come from untrusted sources.
            // For this mock bot, sender is 'You' or 'AI Assistant'. Message from AI includes HTML.
            messageElement.innerHTML = `<strong>${sender}:</strong> ${message}`;
            chatDisplay.appendChild(messageElement);
            chatDisplay.scrollTop = chatDisplay.scrollHeight;
        }

        function fetchChatbotResponse(query) {
            fetch(`/chatbot_query?query=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        appendMessageToChat('AI Assistant', `Error: ${data.error}`);
                    } else {
                        appendMessageToChat('AI Assistant', data.response);
                    }
                })
                .catch(error => {
                    console.error('Error fetching chatbot response:', error);
                    appendMessageToChat('AI Assistant', 'Sorry, I encountered an error trying to respond.');
                });
        }
    });
    // CHATBOT_JS_END
