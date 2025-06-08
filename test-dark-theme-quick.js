// 🚀 QUICK TEST - TEMA OSCURO DEV-TOOLS
// Script rápido para verificar el tema oscuro

console.clear();
console.log('🌙 Quick Test: Dev-Tools Dark Theme');

// Check básico
const isDark = getComputedStyle(document.body).backgroundColor.includes('26, 29, 35');
const hasClass = document.body.classList.contains('dev-tools-active');
const cards = document.querySelectorAll('.dev-tools-dashboard .card.bg-dark').length;

console.log('🎨 Fondo oscuro:', isDark ? '✅' : '❌');
console.log('🏷️ Clase activa:', hasClass ? '✅' : '❌');
console.log('📊 Cards oscuras:', cards, cards > 0 ? '✅' : '❌');
console.log('📦 Assets CSS:', document.querySelectorAll('link[href*="dev-tools"]').length);

if (isDark && hasClass && cards > 0) {
    console.log('🎉 TEMA OSCURO: ✅ FUNCIONANDO');
} else {
    console.log('⚠️ TEMA OSCURO: ❌ REVISAR');
}
