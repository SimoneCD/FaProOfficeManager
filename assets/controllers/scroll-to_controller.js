import ScrollTo from 'stimulus-scroll-to';

export default class extends ScrollTo {
    connect() {
        super.connect();
    }

    // You can set default options in this getter for all your anchors.
    get defaultOptions() {
        return {
            offset: 100,
            behavior: 'auto',
        };
    }
}
// <a
//     href="#awesome-stuff-here"
//     data-controller="scroll-to"
//     data-scroll-to-offset-value="150"
//     data-scroll-to-behavior-value="auto"
// >Scroll to #awesome-stuff-here</a
// >
//
// <h2 id="awesome-stuff-here">Awesome stuff here</h2>
// data-scroll-to-offset-value	10	Offset in pixels from top of the element.	✅
// data-scroll-to-behavior-value	smooth	The scroll behavior. auto or smooth.	✅
