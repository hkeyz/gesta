<link href="{{ asset('css/tailwind/app.css?v='.$asset_v) }}" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/vendor.css?v='.$asset_v) }}">

@if( in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) )
	<link rel="stylesheet" href="{{ asset('css/rtl.css?v='.$asset_v) }}">
@endif

@yield('css')

<!-- app css -->
<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">

@if(isset($pos_layout) && $pos_layout)
	<style type="text/css">
		.content{
			padding-bottom: 0px !important;
		}
	</style>
@endif
<style type="text/css">
	/*
	* Pattern lock css
	* Pattern direction
	* http://ignitersworld.com/lab/patternLock.html
	*/
	.patt-wrap {
	  z-index: 10;
	}
	.patt-circ.hovered {
	  background-color: #cde2f2;
	  border: none;
	}
	.patt-circ.hovered .patt-dots {
	  display: none;
	}
	.patt-circ.dir {
	  background-image: url("{{asset('/img/pattern-directionicon-arrow.png')}}");
	  background-position: center;
	  background-repeat: no-repeat;
	}
	.patt-circ.e {
	  -webkit-transform: rotate(0);
	  transform: rotate(0);
	}
	.patt-circ.s-e {
	  -webkit-transform: rotate(45deg);
	  transform: rotate(45deg);
	}
	.patt-circ.s {
	  -webkit-transform: rotate(90deg);
	  transform: rotate(90deg);
	}
	.patt-circ.s-w {
	  -webkit-transform: rotate(135deg);
	  transform: rotate(135deg);
	}
	.patt-circ.w {
	  -webkit-transform: rotate(180deg);
	  transform: rotate(180deg);
	}
	.patt-circ.n-w {
	  -webkit-transform: rotate(225deg);
	   transform: rotate(225deg);
	}
	.patt-circ.n {
	  -webkit-transform: rotate(270deg);
	  transform: rotate(270deg);
	}
	.patt-circ.n-e {
	  -webkit-transform: rotate(315deg);
	  transform: rotate(315deg);
	}

	/* Modern Sidebar Icons Styling */
	#side-bar a i,
	#side-bar a svg.tw-shrink-0 {
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
		font-size: 1.15rem !important;
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
	}

	/* Sequence of beautiful vivid colors for sidebar menu icons */
	#side-bar > a:nth-of-type(1) i, #side-bar > a:nth-of-type(1) svg.tw-shrink-0,
	#side-bar > div:nth-of-type(1) > a i, #side-bar > div:nth-of-type(1) > a svg.tw-shrink-0 { color: #3b82f6 !important; } /* Blue - Dashboard */

	#side-bar > div:nth-of-type(2) > a i, #side-bar > div:nth-of-type(2) > a svg.tw-shrink-0 { color: #10b981 !important; } /* Green - Users */

	#side-bar > div:nth-of-type(3) > a i, #side-bar > div:nth-of-type(3) > a svg.tw-shrink-0 { color: #f59e0b !important; } /* Amber - Contacts */

	#side-bar > div:nth-of-type(4) > a i, #side-bar > div:nth-of-type(4) > a svg.tw-shrink-0 { color: #ec4899 !important; } /* Pink - Products */

	#side-bar > div:nth-of-type(5) > a i, #side-bar > div:nth-of-type(5) > a svg.tw-shrink-0 { color: #8b5cf6 !important; } /* Purple - Purchases */

	#side-bar > div:nth-of-type(6) > a i, #side-bar > div:nth-of-type(6) > a svg.tw-shrink-0 { color: #14b8a6 !important; } /* Teal - Sales */

	#side-bar > div:nth-of-type(7) > a i, #side-bar > div:nth-of-type(7) > a svg.tw-shrink-0 { color: #ef4444 !important; } /* Red - Expenses */

	#side-bar > div:nth-of-type(8) > a i, #side-bar > div:nth-of-type(8) > a svg.tw-shrink-0 { color: #06b6d4 !important; } /* Cyan - Sell Return */

	#side-bar > div:nth-of-type(9) > a i, #side-bar > div:nth-of-type(9) > a svg.tw-shrink-0 { color: #f97316 !important; } /* Orange - Stock Trans */

	#side-bar > div:nth-of-type(10) > a i, #side-bar > div:nth-of-type(10) > a svg.tw-shrink-0 { color: #a855f7 !important; } /* Indigo - Stock Adj */

	#side-bar > div:nth-of-type(11) > a i, #side-bar > div:nth-of-type(11) > a svg.tw-shrink-0 { color: #6366f1 !important; } /* Blue/Indigo - Cash Register */

	#side-bar > div:nth-of-type(12) > a i, #side-bar > div:nth-of-type(12) > a svg.tw-shrink-0 { color: #10b981 !important; } /* Emerald - Reports */

	#side-bar > div:nth-of-type(13) > a i, #side-bar > div:nth-of-type(13) > a svg.tw-shrink-0 { color: #64748b !important; } /* Slate - Settings */

	#side-bar > div:nth-of-type(14) > a i, #side-bar > div:nth-of-type(14) > a svg.tw-shrink-0 { color: #a855f7 !important; } /* Purple - Extensions */

	#side-bar > div:nth-of-type(15) > a i, #side-bar > div:nth-of-type(15) > a svg.tw-shrink-0 { color: #f43f5e !important; } /* Rose - System Settings */

	/* Active sidebar item animations and style enhancements */
	#side-bar a:hover i,
	#side-bar a:hover svg.tw-shrink-0 {
		transform: scale(1.18) rotate(3deg) !important;
	}

	#side-bar .chiled a i,
	#side-bar .chiled a svg.tw-shrink-0 {
		font-size: 0.85rem !important;
		opacity: 0.65 !important;
		color: #94a3b8 !important;
	}

	#side-bar .chiled a:hover i,
	#side-bar .chiled a:hover svg.tw-shrink-0 {
		opacity: 1 !important;
		color: #475569 !important;
		transform: translateX(3px) scale(1.05) !important;
	}
</style>
@if(!empty($__system_settings['additional_css']))
    {!! $__system_settings['additional_css'] !!}
@endif

