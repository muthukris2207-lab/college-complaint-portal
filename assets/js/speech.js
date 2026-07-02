/**
 * Web Speech API Integration (Speech-to-Text)
 * AI-Powered Smart Complaint & Escalation System
 */

document.addEventListener('DOMContentLoaded', () => {
    const voiceBtn = document.getElementById('voice-input-btn');
    const complaintTextarea = document.getElementById('complaint_text');

    if (!voiceBtn || !complaintTextarea) {
        return; // Element not present on current page
    }

    // Check for speech recognition support
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    if (!SpeechRecognition) {
        // Speech recognition not supported in this browser, hide or disable the button
        voiceBtn.style.display = 'none';
        console.warn('Web Speech API is not supported in this browser. Voice input button hidden.');
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    let isRecording = false;

    voiceBtn.addEventListener('click', (e) => {
        e.preventDefault();
        
        if (isRecording) {
            recognition.stop();
        } else {
            recognition.start();
        }
    });

    recognition.onstart = () => {
        isRecording = true;
        voiceBtn.classList.add('recording');
        // Update Lucide icon inside button if it's there
        const icon = voiceBtn.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', 'mic-off');
            if (window.lucide) window.lucide.createIcons();
        }
        complaintTextarea.placeholder = "Listening... Speak clearly into your microphone.";
    };

    recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        
        // Append transcribed text
        if (complaintTextarea.value.trim() !== '') {
            complaintTextarea.value += ' ' + transcript;
        } else {
            complaintTextarea.value = transcript;
        }
    };

    recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        stopRecording();
        alert('Voice input error: ' + event.error);
    };

    recognition.onend = () => {
        stopRecording();
    };

    function stopRecording() {
        isRecording = false;
        voiceBtn.classList.remove('recording');
        const icon = voiceBtn.querySelector('i');
        if (icon) {
            icon.setAttribute('data-lucide', 'mic');
            if (window.lucide) window.lucide.createIcons();
        }
        complaintTextarea.placeholder = "Describe your complaint in detail...";
    }
});
