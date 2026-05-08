// 移动端菜单切换
function toggleMenu() {
    var nav = document.querySelector('.nav');
    nav.classList.toggle('open');
}

// 移动端点击菜单外关闭
document.addEventListener('click', function(e) {
    var nav = document.querySelector('.nav');
    var toggle = document.querySelector('.menu-toggle');
    if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target) && window.innerWidth <= 768) {
        nav.classList.remove('open');
    }
});

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 给所有的表格行添加悬停样式
    var rows = document.querySelectorAll('tr[data-href]');
    rows.forEach(function(row) {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.href;
        });
        row.style.cursor = 'pointer';
    });
});
