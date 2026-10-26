/**
 * CarDeal Frontend JavaScript
 * Handles modals, clock widget, dynamic form population, and notifications.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Clock Widget
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        function updateClock() {
            const now = new Date();
            clockEl.textContent = now.toLocaleDateString(undefined, { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
            }) + ' • ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        updateClock();
        setInterval(updateClock, 1000);
    }

    // 2. Modal open & close triggers
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal-open');
            openModal(modalId);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modalId = this.getAttribute('data-modal-close');
            closeModal(modalId);
        });
    });

    // Close modal on background overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('open');
            }
        });
    });

    // 3. Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

/**
 * Open Modal helper
 */
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
        const firstInput = modal.querySelector('input:not([type=hidden]), select, textarea');
        if (firstInput) firstInput.focus();
    }
}

/**
 * Close Modal helper
 */
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');
    }
}

/**
 * Populate Edit Vehicle Modal
 */
function populateEditVehicle(vehicle) {
    document.getElementById('edit_vehicle_id').value = vehicle.id;
    document.getElementById('edit_brand').value = vehicle.brand;
    document.getElementById('edit_model').value = vehicle.model;
    document.getElementById('edit_year').value = vehicle.year;
    document.getElementById('edit_color').value = vehicle.color;
    document.getElementById('edit_transmission').value = vehicle.transmission;
    document.getElementById('edit_fuel_type').value = vehicle.fuel_type;
    document.getElementById('edit_price').value = vehicle.price;
    document.getElementById('edit_mileage').value = vehicle.mileage;
    document.getElementById('edit_vin').value = vehicle.vin;
    document.getElementById('edit_status').value = vehicle.status;
    document.getElementById('edit_image_url').value = vehicle.image_url || '';
    document.getElementById('edit_description').value = vehicle.description || '';

    openModal('editVehicleModal');
}

/**
 * Populate Edit Inquiry Modal
 */
function populateEditInquiry(inquiry) {
    document.getElementById('edit_inquiry_id').value = inquiry.id;
    document.getElementById('edit_inq_customer').textContent = inquiry.customer_name;
    document.getElementById('edit_inq_status').value = inquiry.status;
    document.getElementById('edit_inq_notes').value = inquiry.staff_notes || '';

    openModal('editInquiryModal');
}

/**
 * Populate Edit Test Drive Modal
 */
function populateEditTestDrive(drive) {
    document.getElementById('edit_drive_id').value = drive.id;
    document.getElementById('edit_drive_customer').textContent = drive.customer_name;
    document.getElementById('edit_drive_status').value = drive.status;
    document.getElementById('edit_drive_notes').value = drive.notes || '';

    openModal('editTestDriveModal');
}

/**
 * Populate Edit User Modal
 */
function populateEditUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_user_status').value = user.status;

    openModal('editUserModal');
}
