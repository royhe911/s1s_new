$(function() {
    function f(l) {
        var k = 0;
        $(l).each(function() {
            k += $(this).outerWidth(true)
        });
        return k
    }

    function g(n) {
        var o = f($(n).prevAll()),
            q = f($(n).nextAll());
        var l = f($(".content-tabs").children().not(".J_menuTabs"));
        var k = $(".content-tabs").outerWidth(true) - l;
        var p = 0;
        if ($(".page-tabs-content").outerWidth() < k) {
            p = 0
        } else {
            if (q <= (k - $(n).outerWidth(true) - $(n).next().outerWidth(true))) {
                if ((k - $(n).next().outerWidth(true)) > q) {
                    p = o;
                    var m = n;
                    while ((p - $(m).outerWidth()) > ($(".page-tabs-content").outerWidth() - k)) {
                        p -= $(m).prev().outerWidth();
                        m = $(m).prev()
                    }
                }
            } else {
                if (o > (k - $(n).outerWidth(true) - $(n).prev().outerWidth(true))) {
                    p = o - $(n).prev().outerWidth(true)
                }
            }
        }
        $(".page-tabs-content").animate({
            marginLeft: 0 - p + "px"
        }, "fast")
    }

    function a() {
        var o = Math.abs(parseInt($(".page-tabs-content").css("margin-left")));
        var l = f($(".content-tabs").children().not(".J_menuTabs"));
        var k = $(".content-tabs").outerWidth(true) - l;
        var p = 0;
        if ($(".page-tabs-content").width() < k) {
            return false
        } else {
            var m = $(".J_menuTab:first");
            var n = 0;
            while ((n + $(m).outerWidth(true)) <= o) {
                n += $(m).outerWidth(true);
                m = $(m).next()
            }
            n = 0;
            if (f($(m).prevAll()) > k) {
                while ((n + $(m).outerWidth(true)) < (k) && m.length > 0) {
                    n += $(m).outerWidth(true);
                    m = $(m).prev()
                }
                p = f($(m).prevAll())
            }
        }
        $(".page-tabs-content").animate({
            marginLeft: 0 - p + "px"
        }, "fast")
    }

    function b() {
        var o = Math.abs(parseInt($(".page-tabs-content").css("margin-left")));
        var l = f($(".content-tabs").children().not(".J_menuTabs"));
        var k = $(".content-tabs").outerWidth(true) - l;
        var p = 0;
        if ($(".page-tabs-content").width() < k) {
            return false
        } else {
            var m = $(".J_menuTab:first");
            var n = 0;
            while ((n + $(m).outerWidth(true)) <= o) {
                n += $(m).outerWidth(true);
                m = $(m).next()
            }
            n = 0;
            while ((n + $(m).outerWidth(true)) < (k) && m.length > 0) {
                n += $(m).outerWidth(true);
                m = $(m).next()
            }
            p = f($(m).prevAll());
            if (p > 0) {
                $(".page-tabs-content").animate({
                    marginLeft: 0 - p + "px"
                }, "fast")
            }
        }
    }

    function s() {
        var tabs = window.sessionStorage.getItem('tabs');
        u = location.href
        if (u.indexOf('#') < 0) {
            return false
        }
        var i = u.substring(u.indexOf('#') + 1);
        var tabs_arr = tabs.split(',');
        for (var j = 0; j < tabs_arr.length; j++) {
            var ni = tabs_arr[j].substring(1);
            var ic = 'J_menuTab';
            if (ni == i) {
                ic = 'active J_menuTab';
                $('.J_menuTab:first').removeClass('active')
            }
            var nu = '/' + ni.replace('_', '/');
            var np = '<a href="#' + ni + '" class="' + ic + '" data-id="#' + ni + '">' + menu_str[ni] + ' <i class="fa fa-times-circle"></i></a>';
            // $(".J_menuTab").removeClass("active");
            $(".J_menuTabs .page-tabs-content").append(np);
            var nn = '<iframe class="J_iframe" id="' + ni + '" name="' + ni + '" width="100%" height="100%" src="' + nu + '" frameborder="0" data-id="#' + ni + '" seamless></iframe>';
            $(".J_mainContent").find("iframe.J_iframe").hide().parents(".J_mainContent").append(nn);
            g($(".J_menuTab.active"))
        }
        $(".J_iframe").on('iframe').each(function() {
            if ($(this).data("id") == ('#' + i)) {
                $(this).show().siblings(".J_iframe").hide();
                return false
            }
        });
        $('.J_menuItem').each(function() {
            if ($(this).attr('href') == '#' + i) {
                $(this).parent().parent().parent().find('a').click()
            }
        })
    }
    s();

    function c() {
        var i = $(this).attr('href'),
            o = $(this).data('url'),
            l = $.trim($(this).text()),
            k = true;
        $id = i.substring(1);
        if (o == undefined || $.trim(o).length == 0) {
            return false
        }
        $(".J_menuTab").each(function() {
            if ($(this).data("id") == i) {
                if (!$(this).hasClass("active")) {
                    $(this).addClass("active").siblings(".J_menuTab").removeClass("active");
                    g(this);
                    $(".J_mainContent .J_iframe").each(function() {
                        if ($(this).data("id") == i) {
                            $(this).show().siblings(".J_iframe").hide();
                            return false
                        }
                    })
                }
                k = false;
                return false
            }
        });
        if (k) {
            var d = location.href;
            d = d.substring(0, d.indexOf('#'));
            var p = '<a href="' + d + i + '" class="active J_menuTab" data-id="' + i + '">' + l + ' <i class="fa fa-times-circle"></i></a>';
            $(".J_menuTab").removeClass("active");
            $(".J_menuTabs .page-tabs-content").append(p);
            var n = '<iframe class="J_iframe" id="' + $id + '" name="' + $id + '" width="100%" height="100%" src="' + o + '" frameborder="0" data-id="' + i + '" seamless></iframe>';
            $(".J_mainContent").find("iframe.J_iframe").hide().parents(".J_mainContent").append(n);
            g($(".J_menuTab.active"))
        } else {
            $('#' + $id).attr('src', o)
        }
        if (window.sessionStorage) {
            var tabs = '';
            $('.J_menuTab').on('a').each(function(i) {
                if ($(this).data('id') && i > 0) {
                    tabs += ',' + $(this).data('id')
                }
            });
            window.sessionStorage.setItem('tabs', tabs.substring(1))
        }
    }
    $(".J_menuItem").on("click", c);

    function h() {
        var m = $(this).parents(".J_menuTab").data("id");
        var l = $(this).parents(".J_menuTab").width();
        var $href = location.href;
        if ($(this).parents(".J_menuTab").hasClass("active")) {
            if ($(this).parents(".J_menuTab").next(".J_menuTab").size()) {
                var k = $(this).parents(".J_menuTab").next(".J_menuTab:eq(0)").data("id");
                $(this).parents(".J_menuTab").next(".J_menuTab:eq(0)").addClass("active");
                $(".J_mainContent .J_iframe").each(function() {
                    if ($(this).data("id") == k) {
                        $(this).show().siblings(".J_iframe").hide();
                        return false
                    }
                });
                var n = parseInt($(".page-tabs-content").css("margin-left"));
                if (n < 0) {
                    $(".page-tabs-content").animate({
                        marginLeft: (n + l) + "px"
                    }, "fast")
                }
                $(this).parents(".J_menuTab").remove();
                $(".J_mainContent .J_iframe").each(function() {
                    if ($(this).data("id") == m) {
                        $(this).remove();
                        return false
                    }
                })
            }
            if ($(this).parents(".J_menuTab").prev(".J_menuTab").size()) {
                var k = $(this).parents(".J_menuTab").prev(".J_menuTab:last").data("id");
                $(this).parents(".J_menuTab").prev(".J_menuTab:last").addClass("active");
                $(".J_mainContent .J_iframe").each(function() {
                    if ($(this).data("id") == k) {
                        $(this).show().siblings(".J_iframe").hide();
                        return false
                    }
                });
                $(this).parents(".J_menuTab").remove();
                $(".J_mainContent .J_iframe").each(function() {
                    if ($(this).data("id") == m) {
                        $(this).remove();
                        return false
                    }
                })
            }
        } else {
            $(this).parents(".J_menuTab").remove();
            $(".J_mainContent .J_iframe").each(function() {
                if ($(this).data("id") == m) {
                    $(this).remove();
                    return false
                }
            });
            g($(".J_menuTab.active"))
        }
        var vi = '';
        $('.J_menuTab').on('a').not(':first').each(function() {
            if ($(this).data('id')) {
                vi += (',' + $(this).data('id'))
            }
            if ($(this).hasClass('active')) {
                history.pushState(null, '', $href.substring(0, $href.indexOf('#')) + $(this).data('id'))
            }
        });
        if (vi != '') {
            vi = vi.substring(1)
        } else {
            history.pushState(null, '', $href.substring(0, $href.indexOf('#')));
        }
        window.sessionStorage.setItem('tabs', vi);
        return false
    }
    $(".J_menuTabs").on("click", ".J_menuTab i", h);

    function i() {
        $(".page-tabs-content").children("[data-id]").not(":first").not(".active").each(function() {
            $('.J_iframe[data-id="' + $(this).data("id") + '"]').remove();
            $(this).remove()
        });
        $(".page-tabs-content").css("margin-left", "0")
    }
    $(".J_tabCloseOther").on("click", i);

    function j() {
        g($(".J_menuTab.active"))
    }
    $(".J_tabShowActive").on("click", j);

    function e() {
        if (!$(this).hasClass("active")) {
            var k = $(this).data("id");
            $(".J_mainContent .J_iframe").each(function() {
                if ($(this).data("id") == k) {
                    $(this).show().siblings(".J_iframe").hide();
                    return false
                }
            });
            $(this).addClass("active").siblings(".J_menuTab").removeClass("active");
            g(this)
        }
    }
    $(".J_menuTabs").on("click", ".J_menuTab", e);

    function d() {
        var l = $('.J_iframe[data-id="' + $(this).data("id") + '"]');
        var k = l.attr("src")
    }
    $(".J_menuTabs").on("dblclick", ".J_menuTab", d);
    $(".J_tabLeft").on("click", a);
    $(".J_tabRight").on("click", b);
    $(".J_tabCloseAll").on("click", function() {
        $(".page-tabs-content").children("[data-id]").not(":first").each(function() {
            $('.J_iframe[data-id="' + $(this).data("id") + '"]').remove();
            $(this).remove()
        });
        $(".page-tabs-content").children("[data-id]:first").each(function() {
            $('.J_iframe[data-id="' + $(this).data("id") + '"]').show();
            $(this).addClass("active")
        });
        $(".page-tabs-content").css("margin-left", "0")
    })
});