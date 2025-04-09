export const readable = (amount, separator = ",") => {
  // Check for undefined or null amount and return 0
  if (amount === undefined || amount === null) return "0"
  // Convert the amount to a string
  amount = amount.toString()
  // If the amount is not a valid float, return it as is
  if (isNaN(parseFloat(amount)) || parseFloat(amount).toString() !== amount) {
    return amount
  }
  // Split the amount into whole and decimal parts
  let [whole, decimal] = amount.replace(/\s+/g, "").split(".")
  // Format the whole number part with the separator
  whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, separator)
  // Limit the decimal part to two digits, if it exists
  decimal = decimal ? (decimal + "00").slice(0, 2) : ""
  // Combine the whole number and decimal parts
  return decimal ? `${whole}.${decimal}` : whole
}
