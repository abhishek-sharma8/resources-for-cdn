/* 
Theme Name:		 storefront-child-copy
Theme URI:		 http://childtheme-generator.com/
Description:	 storefront-child is a child theme of Storefront, created by ChildTheme-Generator.com
Author:			 Nishit Manjarawala
Author URI:		 http://childtheme-generator.com/
Template:		 storefront
Version:		 1.0.0
Text Domain:	 storefront-child
*/


body{
    font-size: 14px;
    font-family: 'Open Sans', sans-serif;
    font-weight: 400;
    color: #000;
    padding-top:71px;
}
body ul{
	margin-left:0;
}
.page-template-template-homepage:not(.has-post-thumbnail) .site-main {
    padding-top: 0;
}
#primary{
    margin-bottom: 0;
}
@media screen and (max-width: 479px){
    body{padding-top:48px;}
}
/*Nishit Manjarawala (Full Width Product Summary)*/
.woocommerce-page div.product div.summary {
    float: left !important;
}
.pmnm-right-product-price{
	float: right !important;
}
.woocommerce-variation-price{display:none}
.hide{display: none;}
.optional{display: none;}
.woocommerce-additional-fields{display: none;}


/* Season Ends */
.pmn-season-end{
    display: none;
}
body.pmn-season-ends .pmn-season-end{
    display: block;
}
body.pmn-season-ends{
    position: relative;
}
body.pmn-season-ends .pmn-season-end{
    display: block !important;
    background: rgba(0, 0, 0, 0.5);
    padding: 30px 0px;
    position: absolute;
    left: 0;
    right: 0;
    top: 80px;
    bottom: 0;
    z-index: 101;
}
.pmn-season-end .panel.panel-default .panel-heading,
.pmn-season-end .panel.panel-default .panel-body {
    background: #fff;
}
.pmn-season-end .panel.panel-default .panel-heading h3.pm-discontinue-heading {
    font-size: 30px;
    line-height: 1.3;
    color: #000;
    margin: 10px 10px 0 10px;
}
.pmn-content-box{
    margin: auto;
    /*padding-top: 40px;*/
}
body.pmn-season-ends div.pm-header-cart{
    pointer-events: none;
}
body.pmn-season-ends  .panel {
    margin: 0 auto 20px;
    background-color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.05);
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
    width: 75%;
}
.pm-discontinue-desc {
    display: block;
    margin: unset;
    padding: 10px;
}
div.pm-notification-form{
    padding: 0 25px;
}
body.pmn-season-ends .gform_wrapper ul li.gfield {
    margin-top: 10px;
    padding-top: 0;
}
form.pmn-season-end-form .gform_footer input[type=submit] {
    font-size: 18px;
    display: inline-block;
    background-color: #F96C00;
    border-radius: 25px;
    padding: 6px 20px;
    font-weight: 600;
    color: #fff;
}
@media screen and (max-width: 768px) {
    body.pmn-season-ends .pmn-season-end{
        top: 50px;
        height: calc(100% + 170px);
    }
    body.pmn-season-ends  .panel {
        width: 90%;
    }
    .pmn-season-end .panel.panel-default .panel-heading h3.pm-discontinue-heading {
        font-size: 25px;
    }
    body.pmn-season-ends .product-od {
        z-index: 100;
    }
    form.pmn-season-end-form .gform_footer input[type=submit] {
        width: unset;
        padding: 2px 15px;
    }
}
/* Season Ends */
#seson_ends .panel-default{
    margin: auto;
    padding: 25px 0px;
}
