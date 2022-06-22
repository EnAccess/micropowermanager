import Repository from '../repositories/RepositoryFactory'
import {Paginator} from '@/classes/paginator'
import {resources} from '@/resources'


export class BookKeepingService {
    constructor() {
        this.repository = Repository.get('bookKeeping')
        this.bookKeeping = {
            id: null,
            date: null,
            path: null
        }
        this.list = []
        this.paginator = new Paginator(resources.bookKeeping.list)
    }

    updateList(bookKeepings) {

        for (let index in bookKeepings) {
            let bookKeeping = {
                id: bookKeepings[index].id,
                date: bookKeepings[index].date,
                path: bookKeepings[index].path
            }
            this.list.push(bookKeeping)

        }
        return this.list
    }

    exportBookKeeping(id,reference) {
        return this.repository.download(id,reference)
    }

    showAll() {
    }
}
