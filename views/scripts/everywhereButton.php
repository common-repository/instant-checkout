<script type="text/javascript">
    const updateInstantEverywhereButtonView = (total) => {
        const totalVal = calculateTotal(total);
        if (totalVal > 0) {
            document.getElementById("instant-everywhere-button-total").innerText = total.currencyCode + "" + totalVal;
            document.getElementById("instant-everywhere-button").style.display = "block";
            document.getElementById("instant-everywhere-button").style.visibility = "visible";
            document.getElementById("instant-everywhere-button").style.opacity = "1";
        } else {
            document.getElementById("instant-everywhere-button").style.opacity = "0";
            setTimeout(() => {
                document.getElementById("instant-everywhere-button").style.visibility = "hidden";
                document.getElementById("instant-everywhere-button").style.display = "none";
            }, 700);
        }
    }

    const updateInstantEverywherButton = async () => {
        const cart = await getCartObject();
        updateInstantEverywhereButtonView(cart.total);
    };

    window.addEventListener("load", updateInstantEverywherButton);
    window.addEventListener("click", updateInstantEverywherButton);
</script>