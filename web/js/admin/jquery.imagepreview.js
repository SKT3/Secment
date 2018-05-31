var xOffset = 10;
var yOffset = 30;
var previewId = 'preview';

$(function(){
        $(document).on('mousemove', '.dd-options li', function( e ) {

        $( '#' + previewId )
            .css("top",(e.pageY - xOffset) + "px")
            .css("left",(e.pageX + yOffset) + "px");
    });

    $(document).on('mouseenter', '.dd-options li', function( e ) {
   
        // Remove any existing instances of the preview element.

        $( '#' + previewId ).remove();

        // Find the title.

        this.t = $( this ).find( '.dd-option-text' ).text();
        
        // Find the image URL.
        
        this.imgUrl = $( this ).find( '.dd-option-image' ).attr( 'src' );
        
        var c = (this.t != "") ? this.t : "";
        
        // Setup the preview element and make sure it's hiden to start.
        
        var previewElement = $( '<p></p>' ).attr( 'id', previewId ).hide();
        
        // Load the image via AJAX and add it to the preview element when it's done.
        
        $( '<img />' ).attr( 'alt', c ).attr( 'src', this.imgUrl ).attr('width', 600).load( function () {
        
            $( '#' + previewId ).html( $( this ) );
        
        });
        
        // Load the preview element into the DOM.
        
        $( 'body' ).append( previewElement );
        
        // Set the position of the preview element to match the cursor and fade it in.
                         
        $( '#' + previewId )
            .css("top",(e.pageY - xOffset) + "px")
            .css("left",(e.pageX + yOffset) + "px")
            .fadeIn("fast");
                            
    });
            
    $(document).on('mouseleave', '.dd-options li', function( e ) {
        $( '#' + previewId ).fadeOut();
    });
});