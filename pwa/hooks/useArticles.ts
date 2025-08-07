import { useEffect, useState } from "react";
import type { IArticle } from "../components/admin/ArticlesList";
import type { IArticleFormData } from "../components/admin/ArticleForm";

interface IUser {
  email: string;
}

interface IApiResponse {
  "hydra:member": IArticle[];
}

const API_BASE_URL = "http://localhost:8080";

const useArticles = (user: IUser | null) => {
  const [articles, setArticles] = useState<IArticle[]>([]);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState<IArticleFormData>({
    title: "",
    content: "",
    isPublished: false
  });
  const [editingArticle, setEditingArticle] = useState<IArticle | null>(null);

  const fetchArticles = async (): Promise<void> => {
    try {
      const response = await fetch(`${API_BASE_URL}/api/articles`, {
        credentials: 'include',
        headers: {
          'Accept': 'application/ld+json',
        },
      });
      if (response.ok) {
        const data = await response.json() as IApiResponse;
        setArticles(data["hydra:member"] || []);
      }
    } catch (error) {
      // Silently handle errors - could be logged to monitoring service
    }
  };

  const clearForm = (): void => {
    setFormData({ title: "", content: "", isPublished: false });
    setEditingArticle(null);
  };

  const handleSubmit = async (e: React.FormEvent): Promise<void> => {
    e.preventDefault();
    setLoading(true);

    try {
      const url = editingArticle 
        ? `${API_BASE_URL}/api/articles/${editingArticle.id}`
        : `${API_BASE_URL}/api/articles`;
      
      const method = editingArticle ? 'PUT' : 'POST';
      
      const payload = {
        title: formData.title,
        content: formData.content,
        isPublished: formData.isPublished
      };

      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/ld+json',
          'Accept': 'application/ld+json',
        },
        credentials: 'include',
        body: JSON.stringify(payload),
      });

      if (response.ok) {
        clearForm();
        await fetchArticles();
      }
    } catch (error) {
      // Silently handle errors - could be logged to monitoring service
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (article: IArticle): void => {
    setFormData({
      title: article.title,
      content: article.content,
      isPublished: article.isPublished
    });
    setEditingArticle(article);
  };

  const handleDelete = async (id: number): Promise<void> => {
    const confirmed = window.confirm('Are you sure you want to delete this article?');
    if (!confirmed) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/api/articles/${id}`, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
          'Accept': 'application/ld+json',
        },
      });

      if (response.ok) {
        await fetchArticles();
      }
    } catch (error) {
      // Silently handle errors - could be logged to monitoring service
    }
  };

  const handleTogglePublish = async (id: number, isPublished: boolean): Promise<void> => {
    try {
      const response = await fetch(`${API_BASE_URL}/api/articles/${id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          'Accept': 'application/ld+json',
        },
        credentials: 'include',
        body: JSON.stringify({ isPublished }),
      });

      if (response.ok) {
        await fetchArticles();
      }
    } catch (error) {
      // Silently handle errors - could be logged to monitoring service
    }
  };

  useEffect(() => {
    if (user) {
      void fetchArticles();
    }
  }, [user]);

  return {
    articles,
    formData,
    setFormData,
    editingArticle,
    loading,
    handleSubmit,
    handleEdit,
    handleDelete,
    handleTogglePublish
  };
};

export default useArticles;