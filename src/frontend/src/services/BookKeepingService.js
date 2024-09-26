import { Paginator } from "@/Helpers/Paginator"
import { resources } from "@/resources"
import BookKeepingRepository from "@/repositories/BookKeepingRepository"

export class BookKeepingService {
  constructor() {
    this.repository = BookKeepingRepository
    this.bookKeeping = {
      id: null,
      date: null,
      path: null,
    }
    this.list = []
    this.paginator = new Paginator(resources.bookKeeping.list)
  }

  updateList(bookKeepings) {
    for (let index in bookKeepings) {
      let bookKeeping = {
        id: bookKeepings[index].id,
        date: bookKeepings[index].date,
        path: bookKeepings[index].path,
      }
      this.list.push(bookKeeping)
    }
    return this.list
  }

  exportBookKeeping(id, reference) {
    return this.repository.download(id, reference)
  }

  showAll() {}
}
