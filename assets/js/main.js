/**
 * Hangbok77 - 공통 JS
 * - 알림, 모달, 폼 검증 등 공통 로직
 */

(function () {
  'use strict';
  
  // 모바일 메뉴 토글
  document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.getElementById('mobile-menu-toggle');
    var mobileMenu = document.getElementById('mobile-menu');
    var menuIcon = document.getElementById('menu-icon');
    var closeIcon = document.getElementById('close-icon');
    
    if (menuToggle && mobileMenu && menuIcon && closeIcon) {
      menuToggle.addEventListener('click', function() {
        var isHidden = mobileMenu.classList.contains('hidden');
        
        if (isHidden) {
          // 메뉴 열기
          mobileMenu.classList.remove('hidden');
          menuIcon.classList.add('hidden');
          closeIcon.classList.remove('hidden');
          menuToggle.setAttribute('aria-expanded', 'true');
        } else {
          // 메뉴 닫기
          mobileMenu.classList.add('hidden');
          menuIcon.classList.remove('hidden');
          closeIcon.classList.add('hidden');
          menuToggle.setAttribute('aria-expanded', 'false');
        }
      });
      
      // 메뉴 링크 클릭 시 메뉴 닫기
      var menuLinks = mobileMenu.querySelectorAll('a');
      menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
          mobileMenu.classList.add('hidden');
          menuIcon.classList.remove('hidden');
          closeIcon.classList.add('hidden');
          menuToggle.setAttribute('aria-expanded', 'false');
        });
      });
    }
  });
})();
