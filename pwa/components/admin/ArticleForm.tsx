import React from "react";

interface IArticleFormData {
  title: string;
  content: string;
  isPublished: boolean;
}

interface IArticleFormProps {
  formData: IArticleFormData;
  setFormData: (data: IArticleFormData) => void;
  onSubmit: (e: React.FormEvent) => void;
  isEditing: boolean;
  loading: boolean;
}

const TitleField = ({ 
  value, 
  onChange 
}: { 
  value: string; 
  onChange: (value: string) => void; 
}): JSX.Element => (
  <div>
    <label htmlFor="title" className="block text-sm font-medium text-gray-700">
      Title
    </label>
    <input
      type="text"
      id="title"
      value={value}
      onChange={(e): void => onChange(e.target.value)}
      className="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
      required
    />
  </div>
);

const ContentField = ({ 
  value, 
  onChange 
}: { 
  value: string; 
  onChange: (value: string) => void; 
}): JSX.Element => (
  <div>
    <label htmlFor="content" className="block text-sm font-medium text-gray-700">
      Content
    </label>
    <textarea
      id="content"
      rows={10}
      value={value}
      onChange={(e): void => onChange(e.target.value)}
      className="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
      required
    />
  </div>
);

const PublishCheckbox = ({ 
  checked, 
  onChange 
}: { 
  checked: boolean; 
  onChange: (checked: boolean) => void; 
}): JSX.Element => (
  <div className="flex items-center">
    <input
      type="checkbox"
      id="isPublished"
      checked={checked}
      onChange={(e): void => onChange(e.target.checked)}
      className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
    />
    <label htmlFor="isPublished" className="ml-2 block text-sm text-gray-900">
      Publish immediately
    </label>
  </div>
);

const ArticleForm = ({ 
  formData, 
  setFormData, 
  onSubmit, 
  isEditing, 
  loading 
}: IArticleFormProps): JSX.Element => {
  const safeTitle = formData.title || "";
  const safeContent = formData.content || "";
  const safeIsPublished = Boolean(formData.isPublished);

  return (
    <div className="bg-white shadow rounded-lg p-6 mb-6">
      <h2 className="text-lg font-medium text-gray-900 mb-4">
        {isEditing ? "Edit Article" : "Create New Article"}
      </h2>
      <form onSubmit={onSubmit} className="space-y-4" role="form">
        <TitleField
          value={safeTitle}
          onChange={(title): void => setFormData({ ...formData, title })}
        />
        <ContentField
          value={safeContent}
          onChange={(content): void => setFormData({ ...formData, content })}
        />
        <PublishCheckbox
          checked={safeIsPublished}
          onChange={(isPublished): void => setFormData({ ...formData, isPublished })}
        />
        <div className="flex space-x-3">
          <button
            type="submit"
            disabled={loading}
            className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
          >
            {loading ? "Saving..." : (isEditing ? "Update Article" : "Create Article")}
          </button>
          <button
            type="button"
            onClick={(): void => setFormData({ title: "", content: "", isPublished: false })}
            className="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
          >
            Clear
          </button>
        </div>
      </form>
    </div>
  );
};

export default ArticleForm;
export type { IArticleFormData };