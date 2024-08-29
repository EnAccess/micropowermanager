jest.mock("../repositories/TransactionRepository")
import { TransactionService } from "../services/TransactionService"
const transactionService = new TransactionService()

describe("TransactionService #getTransactions", () => {
  it("should list transactions successfully", async () => {
    const data = await transactionService.getTransactions()
    expect(data.length).toBeGreaterThan(0)
  })
})
describe("TransactionService #getTransaction", () => {
  it("should get detail of transaction correctly", async () => {
    const data = await transactionService.getTransaction(96256)
    expect(data.id).toEqual(96256)
  })
  it("should check payment histories for empty states", async () => {
    const data = await transactionService.getTransaction(96256)
    expect(data.payment_histories.length).toBeGreaterThan(0)
  })
})
