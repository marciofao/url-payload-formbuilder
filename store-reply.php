<?php


$post_id = intval($_GET['id']);
$reply_id = intval($_GET['quote_reply']);
$user_data = [];
$user_data['RFQ ID'] =  get_post_meta($post_id, 'rfq_id', true);
$user_data['Project Identifier'] =  get_post_meta($post_id, 'project_identifier', true);

$user_filled = json_decode(get_post_meta($post_id, 'user_filled_fields', true), true);
$user_data = array_merge($user_data, $user_filled);

$user_data['Aditional Details'] =  get_post_meta($post_id, 'additional_details', true);

function upf_get_vintages()
{
    $vintages = [];
    $currentYear = date('Y');
    for ($year = 2020; $year <= $currentYear; $year++) {
        $vintages[] = $year;
    }
    return $vintages;
}

$available_vintages = upf_get_vintages();

?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js" integrity="sha512-foIijUdV0fR0Zew7vmw98E6mOWd9gkGWQBWaoA1EOFAx+pY+N8FmmtIYAVj64R98KeD2wzZh1aHK0JSpKmRH8w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<?php require_once('styling-header.php') ?>
<title>Request for Quote Received</title>
<div class="content">
    <img src="<?php echo plugin_dir_url(__FILE__) ?>/main-logo.svg" alt="Company Logo" class="mb-5 mt-5">
    <div>
        <div class="row">
            <div class="col-md-6">
                <h1>Request for Quote Received</h1>
            </div>
            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        <b>Request Details</b>
                    </div>
                    <div class="col-md-6">
                        <div class="request-id text-end">
                            Request id #: <?php echo $user_data['RFQ ID'] ?>
                        </div>
                    </div>
                </div>
                <div class="top-right-box">
                    <div class="row inner">
                        <div class="col-md-6 gray-text">
                            Market
                        </div>
                        <div class="col-md-6">
                            Voluntary Carbon Market
                        </div>
                        <div class="col-md-6 gray-text">
                            Transaction type
                        </div>
                        <div class="col-md-6">
                            Buy / sell
                        </div>
                        <div class="col-md-6 gray-text">
                            Project ID
                        </div>
                        <div class="col-md-6">
                            <?php echo $user_data['Project Identifier'] ?>
                        </div>
                        <div class="col-md-6 gray-text">
                            Vintage(s)
                        </div>
                        <div class="col-md-6">
                            <?php echo  get_post_meta($post_id, 'vintage', true) ?>
                        </div>
                        <div class="col-md-6 gray-text">
                            Volume (tCO2e)
                        </div>
                        <div class="col-md-6">
                            <?php echo  get_post_meta($post_id, 'volume_tco2e', true) ?>
                        </div>
                        <div class="col-md-6 gray-text">
                            Price (USD)
                        </div>
                        <div class="col-md-6">
                            <?php echo  get_post_meta($post_id, 'price', true) ?>
                        </div>
                        <div class="col-md-6 gray-text">
                            Delivery
                        </div>
                        <div class="col-md-6">
                            <?php echo  get_post_meta($post_id, 'spotdeliverydate', true) ?>
                        </div>
                        <div class="col-md-6 gray-text">
                            Additional Information
                        </div>
                        <div class="col-md-6">
                            <?php echo  get_post_meta($post_id, 'additional_details', true) ?>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <b>Your Response</b>
            </div>
            <div class="col-md-6 mandatory-fields text-end">
                All fields are mandatory
            </div>
            <form class="reply-content col-md-12">
                <div class="repeater">
                    <div data-repeater-list="quote">
                        <div data-repeater-item>
                            <div class="row p-4 pb-0">
                                <div class="col-11">
                                    <div class="row">
                                        <div class="col-md-4 pr-res">
                                            Project ID
                                            <input type="text" name="project_id" class="mb-2">
                                            Vintage
                                            <select name="vintage" class="mb-2">
                                                <?php foreach ($available_vintages as $available_vintage) : ?>
                                                    <option value="<?php echo $available_vintage ?>"><?php echo $available_vintage ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 pr-res">
                                            Delivery
                                            <div class="mb-2">
                                                <label class="wu">
                                                    <input type="radio" name="delivery" value="spot" class="wu">
                                                    Spot
                                                </label>
                                                <label class="wu ml-1">
                                                    <input type="radio" name="delivery" value="forward" class="wu">
                                                    Forward</label>
                                            </div>
                                            Forward Date
                                            <input type="date">
                                        </div>
                                        <div class="col-md-4 pr-res">
                                            Volume (tCO2e)
                                            <input type="text" class="mb-2">
                                            Price (USD)
                                            <input type="text" class="mb-2">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-1 text-end">
                                    <a data-repeater-delete href="#" aria-label="delete" class="remove-quote">✖</a>
                                </div>
                                <hr class="mt-4">
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <a data-repeater-create btn-secondary href="#" class="add-new-quote">
                            Add new quote
                        </a>
                    </div>
                    <br>
                    <br>
                </div>
                Aditional Information
                <textarea name="aditional_info" placeholder="Type Here..."></textarea>
                <div class="text-end">
                    <input type="submit" value="Submit response" class="submit-response-btn mt-4">
                </div>
            </form>




        </div>
        
    </div>
</div>
<?php require_once('footer.php') ?>

<script>
    $(document).ready(function() {
        $('.repeater').repeater({
            // (Optional)
            // start with an empty list of repeaters. Set your first (and only)
            // "data-repeater-item" with style="display:none;" and pass the
            // following configuration flag
            initEmpty: false,

            // (Optional)
            // "show" is called just after an item is added.  The item is hidden
            // at this point.  If a show callback is not given the item will
            // have $(this).show() called on it.
            show: function() {
                $(this).slideDown();
            },
            // (Optional)
            // "hide" is called when a user clicks on a data-repeater-delete
            // element.  The item is still visible.  "hide" is passed a function
            // as its first argument which will properly remove the item.
            // "hide" allows for a confirmation step, to send a delete request
            // to the server, etc.  If a hide callback is not given the item
            // will be deleted.
            hide: function(deleteElement) {
                if (confirm('Are you sure you want to delete this element?')) {
                    $(this).slideUp(deleteElement);
                }
            },

            // (Optional)
            // Removes the delete button from the first list item,
            // defaults to false.
            isFirstItemUndeletable: true
        })
    });
</script>