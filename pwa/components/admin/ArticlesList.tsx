import React from "react";

interface IArticle {
  id: number;
  title: string;
  content: string;
  createdAt: string;
  updatedAt: string;
  isPublished: boolean;
  author: {
    email: string;
  };
  lastModifiedBy?: {
    email: string;
  };
}

interface IArticlesListProps {
  articles: IArticle[];
  onEdit: (article: IArticle) => void;
  onDelete: (id: number) => void;
  onTogglePublish: (id: number, isPublished: boolean) => void;
}

const CONTENT_PREVIEW_LENGTH = 100;

const getStatusBadge = (isPublished: boolean): JSX.Element => (
  <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
    isPublished 
      ? 'bg-green-100 text-green-800' 
      : 'bg-gray-100 text-gray-800'
  }`}>
    {isPublished ? 'Published' : 'Draft'}
  </span>
);

const formatDate = (dateString: string): string => new Date(dateString).toLocaleDateString();

const ArticleRow = ({ 
  article, 
  onEdit, 
  onDelete, 
  onTogglePublish 
}: {
  article: IArticle;
  onEdit: (article: IArticle) => void;
  onDelete: (id: number) => void;
  onTogglePublish: (id: number, isPublished: boolean) => void;
}): JSX.Element => (
  <tr key={article.id}>
    <td className="px-6 py-4 whitespace-nowrap">
      <div className="text-sm font-medium text-gray-900">{article.title}</div>
      <div className="text-sm text-gray-500">
        {article.content.substring(0, CONTENT_PREVIEW_LENGTH)}...
      </div>
    </td>
    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
      {article.author.email}
    </td>
    <td className="px-6 py-4 whitespace-nowrap">
      {getStatusBadge(article.isPublished)}
    </td>
    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
      {formatDate(article.createdAt)}
    </td>
    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
      <button
        onClick={(): void => onEdit(article)}
        className="text-indigo-600 hover:text-indigo-900"
      >
        Edit
      </button>
      <button
        onClick={(): void => onTogglePublish(article.id, !article.isPublished)}
        className="text-blue-600 hover:text-blue-900"
      >
        {article.isPublished ? 'Unpublish' : 'Publish'}
      </button>
      <button
        onClick={(): void => onDelete(article.id)}
        className="text-red-600 hover:text-red-900"
      >
        Delete
      </button>
    </td>
  </tr>
);

const ArticlesList = ({ 
  articles, 
  onEdit, 
  onDelete, 
  onTogglePublish 
}: IArticlesListProps): JSX.Element => (
  <div className="bg-white shadow rounded-lg p-6">
    <h2 className="text-lg font-medium text-gray-900 mb-4">Articles List</h2>
    {articles.length === 0 ? (
      <p className="text-gray-500">No articles found.</p>
    ) : (
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Title
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Author
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Created
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {articles.map((article) => (
              <ArticleRow 
                key={article.id}
                article={article}
                onEdit={onEdit}
                onDelete={onDelete}
                onTogglePublish={onTogglePublish}
              />
            ))}
          </tbody>
        </table>
      </div>
    )}
  </div>
);

export default ArticlesList;
export type { IArticle };