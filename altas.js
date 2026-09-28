function validarSoloNumeros14(input) { input.value = input.value.replace(/[^0-9]/g, '').slice(0, 14); }
function validarSoloLetras(input) { input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''); }
function validarSoloNumeros10(input) { input.value = input.value.replace(/[^0-9]/g, '').slice(0, 10); }

function cerrarModal() {
    const modal = document.getElementById('modalExito');
    if (modal) modal.style.display = 'none';
}

let tutorCount = 0;
const MAX_TUTORES = 4;

function agregarTutor(nombre = '', parentesco = 'Padre', telefono = '') {
    if (tutorCount >= MAX_TUTORES) return;

    const container = document.getElementById('tutores-container');
    const index = tutorCount;
    const block = document.createElement('div');
    block.className = 'tutor-block';
    block.id = `tutor-block-${index}`;

    block.innerHTML = `
        <div class="tutor-block-header">
            <span>👤 Persona Autorizada #${index + 1}</span>
            ${index > 0 ? `<button type="button" class="btn-remove-tutor" onclick="removerTutor(${index})">✕ Eliminar</button>` : ''}
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Nombre del Tutor/Autorizado:</label>
                <input type="text" name="tutores[${index}][nombre]" value="${nombre}" oninput="validarSoloLetras(this)" required>
            </div>
            <div class="form-group">
                <label>Parentesco:</label>
                <select name="tutores[${index}][parentesco]">
                    <option value="Padre" ${parentesco === 'Padre' ? 'selected' : ''}>Padre</option>
                    <option value="Madre" ${parentesco === 'Madre' ? 'selected' : ''}>Madre</option>
                    <option value="Tutor Legal" ${parentesco === 'Tutor Legal' ? 'selected' : ''}>Tutor Legal</option>
                    <option value="Familiar Autorizado" ${parentesco === 'Familiar Autorizado' ? 'selected' : ''}>Familiar Autorizado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Teléfono (10 dígitos):</label>
                <input type="tel" name="tutores[${index}][telefono]" value="${telefono}" maxlength="10" oninput="validarSoloNumeros10(this)" required>
            </div>
        </div>
        <div class="ine-input-box">
            <div class="form-group">
                <label>🪪 Fotografía del INE de esta persona:</label>
                <input type="file" name="tutores_ine[${index}]" accept="image/*" onchange="previewINE(this, 'preview-${index}')" required>
                <img id="preview-${index}" class="preview-single-ine" alt="Vista previa INE">
            </div>
        </div>
    `;

    container.appendChild(block);
    tutorCount++;
    actualizarBotonAgregar();
}

function removerTutor(index) {
    const block = document.getElementById(`tutor-block-${index}`);
    if (block) {
        block.remove();
        tutorCount--;
        actualizarBotonAgregar();
    }
}

function actualizarBotonAgregar() {
    const btn = document.getElementById('btn-add-tutor');
    btn.style.display = (tutorCount >= MAX_TUTORES) ? 'none' : 'inline-flex';
}

function previewINE(input, previewId) {
    const img = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        img.style.display = 'none';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    agregarTutor();
});
