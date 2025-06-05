        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('input', function() {
                if (this.classList.contains('error-highlight')) {
                    this.classList.remove('error-highlight');
                }
            });
        });
        
        document.getElementById('complaintForm').addEventListener('submit', function(e) {
            let valid = true;
            const email = this.elements['email'];
            const subject = this.elements['subject'];
            const message = this.elements['message'];
            
            email.classList.remove('error-highlight');
            subject.classList.remove('error-highlight');
            message.classList.remove('error-highlight');
            
            if (!email.value.trim()) {
                email.classList.add('error-highlight');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('error-highlight');
                valid = false;
            }
            
            if (!subject.value.trim()) {
                subject.classList.add('error-highlight');
                valid = false;
            }
            
            if (!message.value.trim()) {
                message.classList.add('error-highlight');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                const firstError = document.querySelector('.error-highlight');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });