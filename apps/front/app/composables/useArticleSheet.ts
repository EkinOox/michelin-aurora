export interface ArticleItem {
  id?: string
  tag: string
  title: string
  date: string
  read: string
  img: string
  body: string
  url?: string | null
}

export function useArticleSheet() {
  const item = useState<ArticleItem | null>('aurora-article-sheet', () => null)

  function open(a: ArticleItem) {
    item.value = a
  }

  function close() {
    item.value = null
  }

  return { item, open, close }
}
